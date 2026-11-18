<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Api\TodosController Test Case
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
        'app.Todos',
    ];

    /**
     * 未ログインの場合は 401 JSON を返す
     *
     * @return void
     */
    public function testIndexUnauthorized(): void
    {
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->get('/api/todos');

        $this->assertResponseCode(401);
        $this->assertContentType('application/json');
    }

    /**
     * 一覧とページ情報 (総件数) を返す
     *
     * @return void
     */
    public function testIndexReturnsPagination(): void
    {
        $this->session(['Auth' => ['id' => 1, 'email' => 'test@example.com']]);
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->get('/api/todos');

        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertCount(1, $body['todos']);
        $this->assertSame(['page' => 1, 'pageCount' => 1, 'count' => 1], $body['pagination']);
    }
}
