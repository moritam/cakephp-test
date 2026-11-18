<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use Cake\Core\Configure;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Api\TodosController Test Case
 *
 * フィクスチャー: user 1 (alice) が Todo 1 を、user 2 (bob) が Todo 2 を所有する。
 */
class TodosControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.Todos',
    ];

    /**
     * JSON リクエストとして送る
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        // SPA と同じく、CSRF トークンを Cookie と X-CSRF-Token ヘッダーの両方に付ける
        $token = (new CsrfProtectionMiddleware())->createToken();
        $this->cookie('csrfToken', $token);
        $this->configRequest(['headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-CSRF-Token' => $token,
        ]]);
    }

    /**
     * 指定ユーザーでログインした状態にする
     *
     * @param int $userId User id.
     * @return void
     */
    private function loginAs(int $userId): void
    {
        $this->session(['Auth' => ['id' => $userId, 'email' => "user{$userId}@example.com"]]);
    }

    /**
     * レスポンスボディを配列で返す
     *
     * @return array<string, mixed>
     */
    private function json(): array
    {
        return json_decode((string)$this->_response->getBody(), true);
    }

    /**
     * 未ログインの場合は 401 JSON を返す
     *
     * @return void
     */
    public function testIndexUnauthorized(): void
    {
        $this->get('/api/todos');

        $this->assertResponseCode(401);
        $this->assertContentType('application/json');
    }

    /**
     * 一覧は自分の Todo だけを返し、総件数も自分の分だけ
     *
     * @return void
     */
    public function testIndexReturnsOnlyOwnTodos(): void
    {
        $this->loginAs(1);
        $this->get('/api/todos');

        $this->assertResponseOk();
        $body = $this->json();
        $this->assertSame([1], array_column($body['todos'], 'id'));
        $this->assertSame(['page' => 1, 'pageCount' => 1, 'count' => 1], $body['pagination']);
    }

    /**
     * 自分の Todo は取得できる
     *
     * @return void
     */
    public function testViewOwnTodo(): void
    {
        $this->loginAs(1);
        $this->get('/api/todos/1');

        $this->assertResponseOk();
        $this->assertSame(1, $this->json()['todo']['id']);
    }

    /**
     * 他人の Todo は 404 (存在を明かさない)
     *
     * @return void
     */
    public function testViewOtherUsersTodoIsNotFound(): void
    {
        $this->loginAs(1);
        $this->get('/api/todos/2');

        $this->assertResponseCode(404);
    }

    /**
     * 本番設定 (debug = false) でも、API のエラーは JSON で返り、内部の情報は含まない
     *
     * @return void
     */
    public function testNotFoundIsJsonInProduction(): void
    {
        Configure::write('debug', false);
        $this->loginAs(1);
        $this->get('/api/todos/2');

        $this->assertResponseCode(404);
        $this->assertContentType('application/json');
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame(404, $body['code']);
        $this->assertArrayNotHasKey('file', $body);
        $this->assertArrayNotHasKey('trace', $body);
    }

    /**
     * 作成した Todo はログインユーザーのものになる (リクエストの user_id は無視)
     *
     * @return void
     */
    public function testAddAssignsCurrentUser(): void
    {
        $this->loginAs(1);
        $this->post('/api/todos', json_encode(['title' => '新しい Todo', 'user_id' => 2]));

        $this->assertResponseCode(201);
        $id = $this->json()['todo']['id'];
        $todo = $this->getTableLocator()->get('Todos')->get($id);
        $this->assertSame(1, $todo->user_id);
    }

    /**
     * CSRF トークンのない作成リクエストは 403 で拒否され、Todo は作られない
     * (他サイトからのフォーム送信を想定: フォーム形式 + Accept: application/json)
     *
     * @return void
     */
    public function testAddWithoutCsrfTokenIsRejected(): void
    {
        $this->loginAs(1);
        $this->_cookie = [];
        $this->configRequest(['headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]]);
        $this->post('/api/todos', ['title' => 'CSRF']);

        $this->assertResponseCode(403);
        $this->assertFalse($this->getTableLocator()->get('Todos')->exists(['title' => 'CSRF']));
    }

    /**
     * 自分の Todo は更新できる
     *
     * @return void
     */
    public function testEditOwnTodo(): void
    {
        $this->loginAs(1);
        $this->put('/api/todos/1', json_encode(['title' => '更新後']));

        $this->assertResponseOk();
        $this->assertSame('更新後', $this->getTableLocator()->get('Todos')->get(1)->title);
    }

    /**
     * 他人の Todo は更新できない (404、データも変わらない)
     *
     * @return void
     */
    public function testEditOtherUsersTodoIsNotFound(): void
    {
        $this->loginAs(1);
        $this->put('/api/todos/2', json_encode(['title' => '乗っ取り']));

        $this->assertResponseCode(404);
        $this->assertSame('Bob の Todo', $this->getTableLocator()->get('Todos')->get(2)->title);
    }

    /**
     * 更新で user_id を書き換えて所有者を変えることはできない
     *
     * @return void
     */
    public function testEditCannotChangeOwner(): void
    {
        $this->loginAs(1);
        $this->put('/api/todos/1', json_encode(['title' => 'x', 'user_id' => 2]));

        $this->assertResponseOk();
        $this->assertSame(1, $this->getTableLocator()->get('Todos')->get(1)->user_id);
    }

    /**
     * 自分の Todo は削除できる
     *
     * @return void
     */
    public function testDeleteOwnTodo(): void
    {
        $this->loginAs(1);
        $this->delete('/api/todos/1');

        $this->assertResponseCode(204);
        $this->assertFalse($this->getTableLocator()->get('Todos')->exists(['id' => 1]));
    }

    /**
     * 他人の Todo は削除できない (404、データは残る)
     *
     * @return void
     */
    public function testDeleteOtherUsersTodoIsNotFound(): void
    {
        $this->loginAs(1);
        $this->delete('/api/todos/2');

        $this->assertResponseCode(404);
        $this->assertTrue($this->getTableLocator()->get('Todos')->exists(['id' => 2]));
    }
}
