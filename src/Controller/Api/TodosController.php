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
        $defaultDirection = $sortField === 'position' ? 'ASC' : 'DESC';
        $rawDirection = strtolower((string)($this->request->getQuery('direction') ?? $defaultDirection));
        $direction = $rawDirection === 'asc' ? 'ASC' : 'DESC';
        if (!in_array($sortField, ['modified', 'created', 'due_date', 'priority', 'position'], true)) {
            $sortField = 'modified';
        }

        $perPage = min(50, max(1, (int)($this->request->getQuery('per_page') ?? 20)));

        $query = $this->Todos
            ->find('ownedBy', userId: $this->currentUserId())
            ->find('filtered', q: $q, priority: $priority, completed: $completed, tagId: $tagId, overdue: $overdue)
            ->contain(['Tags'])
            ->orderBy(["Todos.{$sortField}" => $direction]);

        $todos = $this->paginate($query, ['limit' => $perPage]);
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
     * CSV エクスポート (フィルター適用・全件・ページネーションなし)
     *
     * @return \Cake\Http\Response
     */
    public function export(): Response
    {
        $this->request->allowMethod(['get']);

        $q = (string)($this->request->getQuery('q') ?? '');
        $priority = (string)($this->request->getQuery('priority') ?? '');
        $completed = (string)($this->request->getQuery('completed') ?? '');
        $tagId = (int)($this->request->getQuery('tag_id') ?? 0);
        $overdue = (bool)($this->request->getQuery('overdue') ?? false);

        $todos = $this->Todos
            ->find('ownedBy', userId: $this->currentUserId())
            ->find('filtered', q: $q, priority: $priority, completed: $completed, tagId: $tagId, overdue: $overdue)
            ->contain(['Tags'])
            ->orderBy(['Todos.modified' => 'DESC'])
            ->all();

        $rows = [];
        $rows[] = ['id', 'title', 'description', 'priority', 'completed', 'due_date', 'tags', 'created', 'modified'];
        foreach ($todos as $todo) {
            $tagNames = implode(',', array_map(fn($t) => $t->name, $todo->tags ?? []));
            $rows[] = [
                $todo->id,
                $todo->title,
                $todo->description ?? '',
                $todo->priority,
                $todo->completed ? '1' : '0',
                $todo->due_date ? $todo->due_date->toDateString() : '',
                $tagNames,
                $todo->created ? $todo->created->toDateTimeString() : '',
                $todo->modified ? $todo->modified->toDateTimeString() : '',
            ];
        }

        $csv = "\xEF\xBB\xBF"; // BOM (Excel UTF-8 対応)
        foreach ($rows as $row) {
            $escaped = array_map(function (mixed $v): string {
                $s = (string)$v;
                if (str_contains($s, ',') || str_contains($s, '"') || str_contains($s, "\n")) {
                    return '"' . str_replace('"', '""', $s) . '"';
                }

                return $s;
            }, $row);
            $csv .= implode(',', $escaped) . "\r\n";
        }

        $filename = 'todos_' . date('Ymd') . '.csv';

        return $this->response
            ->withType('text/csv')
            ->withHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->withStringBody($csv);
    }

    /**
     * Todo の表示順を更新する
     *
     * リクエスト: { ids: [3, 1, 5, ...] } — 新しい表示順の todo ID 配列
     *
     * @return \Cake\Http\Response
     */
    public function reorder(): Response
    {
        $this->request->allowMethod(['post']);
        $ids = $this->request->getData('ids');
        if (!is_array($ids) || count($ids) === 0) {
            return $this->response
                ->withStatus(422)
                ->withType('application/json')
                ->withStringBody((string)json_encode(
                    ['errors' => ['ids' => 'ids は空でない配列を指定してください']],
                    JSON_THROW_ON_ERROR,
                ));
        }

        $userId = $this->currentUserId();
        $ownedIds = $this->Todos->find('ownedBy', userId: $userId)
            ->select(['Todos.id'])
            ->where(['Todos.id IN' => $ids])
            ->all()
            ->map(fn($t) => $t->id)
            ->toArray();

        foreach ($ids as $position => $id) {
            if (!in_array((int)$id, $ownedIds, true)) {
                continue;
            }
            $this->Todos->updateAll(['position' => $position], ['id' => (int)$id]);
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode(['success' => true], JSON_THROW_ON_ERROR));
    }

    /**
     * 統計情報 (合計・完了・未完了・期限切れ・優先度別件数)
     *
     * @return \Cake\Http\Response
     */
    public function stats(): Response
    {
        $this->request->allowMethod(['get']);
        $userId = $this->currentUserId();
        $today = date('Y-m-d');

        $total = $this->Todos->find('ownedBy', userId: $userId)->count();
        $completed = $this->Todos->find('ownedBy', userId: $userId)
            ->where(['Todos.completed' => true])->count();
        $overdue = $this->Todos->find('ownedBy', userId: $userId)
            ->where(['Todos.completed' => false, 'Todos.due_date <' => $today,
                'Todos.due_date IS NOT' => null])->count();

        $byPriority = [];
        foreach (['high', 'medium', 'low'] as $p) {
            $byPriority[$p] = $this->Todos->find('ownedBy', userId: $userId)
                ->where(['Todos.priority' => $p])->count();
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'total' => $total,
                'completed' => $completed,
                'pending' => $total - $completed,
                'overdue' => $overdue,
                'by_priority' => $byPriority,
            ], JSON_THROW_ON_ERROR));
    }

    /**
     * 一括操作 (complete: 完了にする / delete: 削除)
     *
     * リクエスト: { action: 'complete'|'delete', ids: [1, 2, ...] }
     *
     * @return \Cake\Http\Response
     */
    public function bulk(): Response
    {
        $this->request->allowMethod(['post']);

        $action = (string)($this->request->getData('action') ?? '');
        $ids = $this->request->getData('ids');

        if (!in_array($action, ['complete', 'delete'], true) || !is_array($ids) || count($ids) === 0) {
            return $this->response
                ->withStatus(422)
                ->withType('application/json')
                ->withStringBody((string)json_encode(
                    ['errors' => ['action と ids (空でない配列) を指定してください']],
                    JSON_THROW_ON_ERROR,
                ));
        }

        $userId = $this->currentUserId();
        $ids = array_map('intval', $ids);

        if ($action === 'delete') {
            $count = $this->Todos->deleteAll(['id IN' => $ids, 'user_id' => $userId]);
        } else {
            $count = $this->Todos->updateAll(['completed' => true], ['id IN' => $ids, 'user_id' => $userId]);
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode(['success' => true, 'count' => $count], JSON_THROW_ON_ERROR));
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
