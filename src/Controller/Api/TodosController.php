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
     * Index (list with pagination)
     *
     * @return \Cake\Http\Response
     */
    public function index(): Response
    {
        $this->request->allowMethod(['get']);
        $query = $this->Todos->find('ownedBy', userId: $this->currentUserId())
            ->orderByDesc('modified');
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
        $todo = $this->getOwnedTodo($id);

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
        $todo = $this->Todos->patchEntity($todo, $this->request->getData());
        $todo->user_id = $this->currentUserId();
        if ($this->Todos->save($todo)) {
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
        $todo = $this->getOwnedTodo($id);
        $todo = $this->Todos->patchEntity($todo, $this->request->getData());
        if ($this->Todos->save($todo)) {
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
     * @return \App\Model\Entity\Todo
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     */
    private function getOwnedTodo(?string $id): Todo
    {
        /** @var \App\Model\Entity\Todo */
        return $this->Todos->find('ownedBy', userId: $this->currentUserId())
            ->where(['Todos.id' => (int)$id])
            ->firstOrFail();
    }
}
