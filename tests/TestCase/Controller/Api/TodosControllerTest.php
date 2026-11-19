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

    /**
     * per_page パラメーターで取得件数を制御できる
     *
     * @return void
     */
    public function testIndexPagination(): void
    {
        // alice の Todo をもう 1 件追加して合計 2 件にする
        $Todos = $this->getTableLocator()->get('Todos');
        $todo = $Todos->newEntity(['title' => 'Second Todo', 'completed' => false, 'position' => 10]);
        $todo->user_id = 1;
        $Todos->save($todo);

        $this->loginAs(1);
        $this->get('/api/todos?per_page=1&page=1');

        $this->assertResponseOk();
        $body = $this->json();
        $this->assertCount(1, $body['todos']);
        $this->assertSame(2, $body['pagination']['count']);
        $this->assertSame(2, $body['pagination']['pageCount']);
        $this->assertSame(1, $body['pagination']['page']);
    }

    /**
     * per_page の上限は 50
     *
     * @return void
     */
    public function testIndexPerPageCappedAt50(): void
    {
        $this->loginAs(1);
        $this->get('/api/todos?per_page=999');

        $this->assertResponseOk();
        // 件数は 1 件のままだが、limit が 50 に丸められて例外にならない
        $this->assertSame(1, $this->json()['pagination']['count']);
    }

    /**
     * CSV エクスポートは text/csv を返し、BOM と正しいヘッダー行を含む
     *
     * @return void
     */
    public function testExportCsv(): void
    {
        $this->loginAs(1);
        $this->get('/api/todos/export');

        $this->assertResponseOk();
        $this->assertContentType('text/csv');
        $body = (string)$this->_response->getBody();
        // BOM チェック
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body);
        // ヘッダー行チェック
        $this->assertStringContainsString('id,title,description,priority,completed,due_date,tags,created,modified', $body);
        // alice の Todo 1 件だけ含まれる (bob の Todo は含まれない)
        $this->assertStringContainsString('Lorem ipsum dolor sit amet', $body);
        $this->assertStringNotContainsString('Bob の Todo', $body);
    }

    /**
     * CSV エクスポートには Content-Disposition ヘッダーが付く
     *
     * @return void
     */
    public function testExportCsvContentDisposition(): void
    {
        $this->loginAs(1);
        $this->get('/api/todos/export');

        $this->assertResponseOk();
        $disposition = $this->_response->getHeaderLine('Content-Disposition');
        $this->assertStringStartsWith('attachment; filename=', $disposition);
        $this->assertStringContainsString('.csv', $disposition);
    }

    /**
     * 未ログインのエクスポートは 401
     *
     * @return void
     */
    public function testExportUnauthorized(): void
    {
        $this->get('/api/todos/export');

        $this->assertResponseCode(401);
    }

    /**
     * 並び替えで position が更新される
     *
     * @return void
     */
    public function testReorder(): void
    {
        // alice の Todo をもう 1 件追加
        $Todos = $this->getTableLocator()->get('Todos');
        $second = $Todos->newEntity(['title' => 'Second', 'completed' => false, 'position' => 10]);
        $second->user_id = 1;
        $todo2 = $Todos->save($second);

        $this->loginAs(1);
        // id=1 を 2番目、$todo2 を 1番目に並び替え
        $this->post('/api/todos/reorder', json_encode(['ids' => [$todo2->id, 1]]));

        $this->assertResponseOk();
        $this->assertSame(['success' => true], $this->json());

        $Todos = $this->getTableLocator()->get('Todos');
        $this->assertSame(0, (int)$Todos->get($todo2->id)->position);
        $this->assertSame(1, (int)$Todos->get(1)->position);
    }

    /**
     * 他人の Todo ID を含む並び替えは、その ID だけスキップされ、自分のものは更新される
     *
     * @return void
     */
    public function testReorderIgnoresOtherUsersTodos(): void
    {
        $this->loginAs(1);
        // id=2 は bob の Todo → スキップされ、id=1 の position だけ更新される
        $this->post('/api/todos/reorder', json_encode(['ids' => [2, 1]]));

        $this->assertResponseOk();
        $Todos = $this->getTableLocator()->get('Todos');
        $this->assertSame(1, (int)$Todos->get(1)->position); // インデックス 1
        $this->assertSame(2, (int)$Todos->get(2)->position); // 変更されない (フィクスチャー値)
    }

    /**
     * ids が空配列の場合は 422
     *
     * @return void
     */
    public function testReorderEmptyIdsIsUnprocessable(): void
    {
        $this->loginAs(1);
        $this->post('/api/todos/reorder', json_encode(['ids' => []]));

        $this->assertResponseCode(422);
    }

    /**
     * 一括完了で指定した自分の Todo が completed = true になる
     *
     * @return void
     */
    public function testBulkComplete(): void
    {
        $this->loginAs(1);
        $this->post('/api/todos/bulk', json_encode(['action' => 'complete', 'ids' => [1]]));

        $this->assertResponseOk();
        $body = $this->json();
        $this->assertSame(true, $body['success']);
        $this->assertSame(1, $body['count']);
        $this->assertTrue((bool)$this->getTableLocator()->get('Todos')->get(1)->completed);
    }

    /**
     * 一括削除で指定した自分の Todo が削除される
     *
     * @return void
     */
    public function testBulkDelete(): void
    {
        $this->loginAs(1);
        $this->post('/api/todos/bulk', json_encode(['action' => 'delete', 'ids' => [1]]));

        $this->assertResponseOk();
        $body = $this->json();
        $this->assertSame(1, $body['count']);
        $this->assertFalse($this->getTableLocator()->get('Todos')->exists(['id' => 1]));
    }

    /**
     * 他人の Todo ID を含む一括操作は、その ID を無視して自分のものだけ処理する
     *
     * @return void
     */
    public function testBulkIgnoresOtherUsersTodos(): void
    {
        $this->loginAs(1);
        // id=2 は bob の Todo → 無視され、count=0
        $this->post('/api/todos/bulk', json_encode(['action' => 'delete', 'ids' => [2]]));

        $this->assertResponseOk();
        $this->assertSame(0, $this->json()['count']);
        $this->assertTrue($this->getTableLocator()->get('Todos')->exists(['id' => 2]));
    }

    /**
     * action が不正な場合は 422
     *
     * @return void
     */
    public function testBulkInvalidAction(): void
    {
        $this->loginAs(1);
        $this->post('/api/todos/bulk', json_encode(['action' => 'invalid', 'ids' => [1]]));

        $this->assertResponseCode(422);
    }

    /**
     * ids が空の場合は 422
     *
     * @return void
     */
    public function testBulkEmptyIds(): void
    {
        $this->loginAs(1);
        $this->post('/api/todos/bulk', json_encode(['action' => 'complete', 'ids' => []]));

        $this->assertResponseCode(422);
    }

    /**
     * position ソートで Todo が position 昇順に返る
     *
     * @return void
     */
    public function testIndexSortByPosition(): void
    {
        $Todos = $this->getTableLocator()->get('Todos');
        $second = $Todos->newEntity(['title' => 'B', 'completed' => false, 'position' => 0]);
        $second->user_id = 1;
        $Todos->save($second);
        $Todos->updateAll(['position' => 99], ['id' => 1]);

        $this->loginAs(1);
        $this->get('/api/todos?sort=position&direction=asc');

        $this->assertResponseOk();
        $ids = array_column($this->json()['todos'], 'position');
        $sorted = $ids;
        sort($sorted);
        $this->assertSame($sorted, $ids);
    }
}
