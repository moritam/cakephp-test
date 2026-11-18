<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

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
     * JSON リクエストを送る準備
     *
     * @return void
     */
    private function asJson(): void
    {
        $this->configRequest(['headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]]);
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
