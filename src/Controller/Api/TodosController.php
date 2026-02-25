<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;
use Cake\Http\Response;

/**
 * API Todos Controller (JSON)
 *
 * @property \App\Model\Table\TodosTable $Todos
 */
class TodosController extends AppController
{
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
        $query = $this->Todos->find()->orderDesc('modified');
        $todos = $this->paginate($query, ['limit' => 100]);
        $pagination = $this->request->getAttribute('paging')['Todos'] ?? [];
        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'todos' => $todos,
                'pagination' => [
                    'page' => $pagination['page'] ?? 1,
                    'pageCount' => $pagination['pageCount'] ?? 1,
                    'count' => $pagination['count'] ?? 0,
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
        $todo = $this->Todos->get($id, contain: []);
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
        $todo = $this->Todos->get($id, contain: []);
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
        $todo = $this->Todos->get($id);
        $this->Todos->delete($todo);
        return $this->response->withStatus(204);
    }
}
