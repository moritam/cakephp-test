<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Core\Configure;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\UsersController Test Case
 */
class UsersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.Users',
    ];

    /**
     * JSON リクエストを送る準備 (SPA と同じく CSRF トークンを Cookie とヘッダーに付ける)
     *
     * @param bool $withCsrf CSRF トークンを付けるか
     * @return void
     */
    private function asJson(bool $withCsrf = true): void
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        if ($withCsrf) {
            $token = (new CsrfProtectionMiddleware())->createToken();
            $this->cookie('csrfToken', $token);
            $headers['X-CSRF-Token'] = $token;
        }
        $this->configRequest(['headers' => $headers]);
    }

    /**
     * CSRF トークンを取得できる (未ログインでも可、Cookie も発行される)
     *
     * @return void
     */
    public function testCsrfToken(): void
    {
        $this->asJson(false);
        $this->get('/users/csrf-token');

        $this->assertResponseOk();
        $this->assertHeader('Cache-Control', 'no-store');
        $this->assertCookieIsSet('csrfToken');
        $token = json_decode((string)$this->_response->getBody(), true)['csrfToken'];
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    /**
     * CSRF トークンのないログインは 403 で拒否され、ログインも成立しない (ログイン CSRF 対策)
     *
     * @return void
     */
    public function testLoginWithoutCsrfTokenIsRejected(): void
    {
        $this->asJson();
        $this->post('/users/add', json_encode(['email' => 'csrf@example.com', 'password' => 'password123']));
        $this->assertResponseCode(201);

        $this->_cookie = [];
        $this->asJson(false);
        $this->post('/users/login', json_encode(['email' => 'csrf@example.com', 'password' => 'password123']));

        $this->assertResponseCode(403);
        $this->assertSessionNotHasKey('Auth');
    }

    /**
     * CSRF トークンのない登録は 403 で拒否され、ユーザーは作られない
     *
     * @return void
     */
    public function testRegisterWithoutCsrfTokenIsRejected(): void
    {
        $this->asJson(false);
        $this->post('/users/add', json_encode(['email' => 'nocsrf@example.com', 'password' => 'password123']));

        $this->assertResponseCode(403);
        $this->assertFalse($this->getTableLocator()->get('Users')->exists(['email' => 'nocsrf@example.com']));
    }

    /**
     * 登録 → ログイン → ログアウトの一連の流れ
     *
     * @return void
     */
    public function testRegisterLoginLogout(): void
    {
        $credentials = json_encode(['email' => 'new@example.com', 'password' => 'password123']);

        $this->asJson();
        $this->post('/users/add', $credentials);
        $this->assertResponseCode(201);
        $this->assertContentType('application/json');

        $this->asJson();
        $this->post('/users/login', $credentials);
        $this->assertResponseOk();
        $this->assertSession('new@example.com', 'Auth.email');

        $this->asJson();
        $this->post('/users/logout');
        $this->assertResponseOk();
        $this->assertSessionNotHasKey('Auth');
    }

    /**
     * 登録時のバリデーションエラーは 422
     *
     * @return void
     */
    public function testRegisterValidationError(): void
    {
        $this->asJson();
        $this->post('/users/add', json_encode(['email' => 'alice@example.com', 'password' => 'short']));

        $this->assertResponseCode(422);
        $this->assertResponseContains('"email"');
        $this->assertResponseContains('"password"');
    }

    /**
     * 新規登録を止めている場合は 403 で、ユーザーは作られない
     *
     * @return void
     */
    public function testRegisterDisabled(): void
    {
        Configure::write('App.registrationEnabled', false);
        $this->asJson();
        $this->post('/users/add', json_encode(['email' => 'blocked@example.com', 'password' => 'password123']));

        $this->assertResponseCode(403);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame('新規登録は現在受け付けていません。', $body['message']);
        $this->assertFalse($this->getTableLocator()->get('Users')->exists(['email' => 'blocked@example.com']));
    }

    /**
     * パスワードが違う場合は 401
     *
     * @return void
     */
    public function testLoginFailure(): void
    {
        $this->asJson();
        $this->post('/users/login', json_encode(['email' => 'alice@example.com', 'password' => 'wrong-password']));

        $this->assertResponseCode(401);
        $this->assertContentType('application/json');
    }

    /**
     * 未ログインでのログアウトも成功扱い
     *
     * @return void
     */
    public function testLogoutWithoutLogin(): void
    {
        $this->asJson();
        $this->post('/users/logout');

        $this->assertResponseOk();
    }

    /**
     * ブラウザで直接開いた場合は SPA の画面へリダイレクト
     *
     * @return void
     */
    public function testGetRedirectsToSpa(): void
    {
        $this->get('/users/login');
        $this->assertRedirect('/app/login');

        $this->get('/users/add');
        $this->assertRedirect('/app/register');

        $this->get('/users/logout');
        $this->assertRedirect('/app/login');
    }

    /**
     * ログイン中のユーザーはパスワードを変更できる
     *
     * @return void
     */
    public function testChangePasswordSuccess(): void
    {
        $this->session(['Auth' => ['id' => 1, 'email' => 'alice@example.com']]);
        $this->asJson();
        $this->patch('/users/change-password', json_encode([
            'current_password' => 'password123',
            'new_password' => 'newpassword456',
            'new_password_confirm' => 'newpassword456',
        ]));

        $this->assertResponseOk();
        $this->assertSame(['success' => true], json_decode((string)$this->_response->getBody(), true));

        // DB のパスワードが実際に変わっている
        $user = $this->getTableLocator()->get('Users')->get(1);
        $hasher = new DefaultPasswordHasher();
        $this->assertTrue($hasher->check('newpassword456', $user->password));
        $this->assertFalse($hasher->check('password123', $user->password));
    }

    /**
     * 現在のパスワードが違う場合は 422
     *
     * @return void
     */
    public function testChangePasswordWrongCurrentPassword(): void
    {
        $this->session(['Auth' => ['id' => 1, 'email' => 'alice@example.com']]);
        $this->asJson();
        $this->patch('/users/change-password', json_encode([
            'current_password' => 'wrongpassword',
            'new_password' => 'newpassword456',
            'new_password_confirm' => 'newpassword456',
        ]));

        $this->assertResponseCode(422);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('current_password', $body['errors']);
    }

    /**
     * 新しいパスワードと確認が一致しない場合は 422
     *
     * @return void
     */
    public function testChangePasswordConfirmMismatch(): void
    {
        $this->session(['Auth' => ['id' => 1, 'email' => 'alice@example.com']]);
        $this->asJson();
        $this->patch('/users/change-password', json_encode([
            'current_password' => 'password123',
            'new_password' => 'newpassword456',
            'new_password_confirm' => 'different789',
        ]));

        $this->assertResponseCode(422);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('new_password_confirm', $body['errors']);
    }

    /**
     * 新しいパスワードが 8 文字未満の場合は 422
     *
     * @return void
     */
    public function testChangePasswordTooShort(): void
    {
        $this->session(['Auth' => ['id' => 1, 'email' => 'alice@example.com']]);
        $this->asJson();
        $this->patch('/users/change-password', json_encode([
            'current_password' => 'password123',
            'new_password' => 'short',
            'new_password_confirm' => 'short',
        ]));

        $this->assertResponseCode(422);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('new_password', $body['errors']);
    }

    /**
     * 未ログインでのパスワード変更は 401
     *
     * @return void
     */
    public function testChangePasswordUnauthorized(): void
    {
        $this->asJson();
        $this->patch('/users/change-password', json_encode([
            'current_password' => 'password123',
            'new_password' => 'newpassword456',
            'new_password_confirm' => 'newpassword456',
        ]));

        $this->assertResponseCode(401);
    }

    /**
     * 削除した旧画面・デバッグ用の URL は公開されていない
     *
     * @return void
     */
    public function testRemovedEndpointsAreNotFound(): void
    {
        $this->session(['Auth' => ['id' => 1, 'email' => 'alice@example.com']]);
        foreach (['/users/db-test', '/todos', '/todos/view/1', '/pages/home', '/users/index'] as $url) {
            $this->get($url);
            $this->assertResponseCode(404, "{$url} should not be routable");
        }
    }
}
