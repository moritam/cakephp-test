<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;
use App\Model\Entity\Todo;
use Cake\Http\Response;

/**
 * API Todos Controller (JSON)
 *
 * すべての操作はログイン中のユーザーが所有する Todo に限定する。
 * 他ユーザーの Todo を指定された場合は、存在を明かさないよう 404 を返す。
 *
 * @property \App\Model\Table\TodosTable $Todos
 */
class TodosController extends AppController
{
    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->viewBuilder()->setOption('serialize', true);
    }

    /**
     * Index (list with pagination, search, filter, sort)
     *
     * Query params:
     *   q          - keyword search on title
     *   priority   - high|medium|low
     *   completed  - 0|1
     *   tag_id     - integer
     *   overdue    - 1
     *   sort       - modified|created|due_date|priority
     *   direction  - asc|desc
     *
     * @return \Cake\Http\Response
     */
    public function index(): Response
    {
        $this->request->allowMethod(['get']);

        $q = (string)($this->request->getQuery('q') ?? '');
        $priority = (string)($this->request->getQuery('priority') ?? '');
        $completed = (string)($this->request->getQuery('completed') ?? '');
        $tagId = (int)($this->request->getQuery('tag_id') ?? 0);
        $overdue = (bool)($this->request->getQuery('overdue') ?? false);

        $sortField = (string)($this->request->getQuery('sort') ?? 'modified');
        $direction = strtolower((string)($this->request->getQuery('direction') ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
        if (!in_array($sortField, ['modified', 'created', 'due_date', 'priority'], true)) {
            $sortField = 'modified';
        }

        $query = $this->Todos
            ->find('ownedBy', userId: $this->currentUserId())
            ->find('filtered', q: $q, priority: $priority, completed: $completed, tagId: $tagId, overdue: $overdue)
            ->contain(['Tags'])
            ->orderBy(["Todos.{$sortField}" => $direction]);

        $todos = $this->paginate($query, ['limit' => 100]);
        $pagination = $todos->pagingParams();

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'todos' => $todos,
                'pagination' => [
                    'page' => $pagination['currentPage'] ?? 1,
                    'pageCount' => $pagination['pageCount'] ?? 1,
                    'count' => $pagination['totalCount'] ?? 0,
                ],
            ], JSON_THROW_ON_ERROR));
    }

    /**
     * View single todo
     *
     * @param string|null $id Todo id.
     * @return \Cake\Http\Response
     */
    public function view(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $todo = $this->getOwnedTodo($id, contain: ['Tags']);

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode(['todo' => $todo], JSON_THROW_ON_ERROR));
    }

    /**
     * Add todo
     *
     * @return \Cake\Http\Response
     */
    public function add(): Response
    {
        $this->request->allowMethod(['post']);
        $todo = $this->Todos->newEmptyEntity();
        $todo = $this->Todos->patchEntity($todo, $this->request->getData(), ['associated' => ['Tags']]);
        $todo->user_id = $this->currentUserId();
        if ($this->Todos->save($todo)) {
            $todo = $this->getOwnedTodo((string)$todo->id, contain: ['Tags']);

            return $this->response
                ->withStatus(201)
                ->withType('application/json')
                ->withStringBody((string)json_encode(['todo' => $todo], JSON_THROW_ON_ERROR));
        }

        return $this->response
            ->withStatus(422)
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'errors' => $todo->getErrors(),
            ], JSON_THROW_ON_ERROR));
    }

    /**
     * Edit todo
     *
     * @param string|null $id Todo id.
     * @return \Cake\Http\Response
     */
    public function edit(?string $id = null): Response
    {
        $this->request->allowMethod(['put', 'patch']);
        $todo = $this->getOwnedTodo($id, contain: ['Tags']);
        $todo = $this->Todos->patchEntity($todo, $this->request->getData(), ['associated' => ['Tags']]);
        if ($this->Todos->save($todo)) {
            $todo = $this->getOwnedTodo((string)$todo->id, contain: ['Tags']);

            return $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode(['todo' => $todo], JSON_THROW_ON_ERROR));
        }

        return $this->response
            ->withStatus(422)
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'errors' => $todo->getErrors(),
            ], JSON_THROW_ON_ERROR));
    }

    /**
     * Delete todo
     *
     * @param string|null $id Todo id.
     * @return \Cake\Http\Response
     */
    public function delete(?string $id = null): Response
    {
        $this->request->allowMethod(['delete', 'post']);
        $todo = $this->getOwnedTodo($id);
        $this->Todos->delete($todo);

        return $this->response->withStatus(204);
    }

    /**
     * ログイン中のユーザー ID
     *
     * @return int
     */
    private function currentUserId(): int
    {
        return (int)$this->Authentication->getIdentity()->getIdentifier();
    }

    /**
     * ログイン中のユーザーが所有する Todo を取得する (なければ 404)
     *
     * @param string|null $id Todo id.
     * @param array<string> $contain 関連モデル
     * @return \App\Model\Entity\Todo
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     */
    private function getOwnedTodo(?string $id, array $contain = []): Todo
    {
        /** @var \App\Model\Entity\Todo */
        return $this->Todos->find('ownedBy', userId: $this->currentUserId())
            ->contain($contain)
            ->where(['Todos.id' => (int)$id])
            ->firstOrFail();
    }
}
