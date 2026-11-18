<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;
use Cake\Http\Response;

/**
 * API Tags Controller (JSON)
 *
 * タグはユーザー共通 (全ユーザーで同じタグを共有する設計)。
 *
 * @property \App\Model\Table\TagsTable $Tags
 */
class TagsController extends AppController
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
     * タグ一覧 (名前の昇順)
     *
     * @return \Cake\Http\Response
     */
    public function index(): Response
    {
        $this->request->allowMethod(['get']);
        $tags = $this->Tags->find()->orderBy(['Tags.name' => 'ASC'])->all();

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode(['tags' => $tags], JSON_THROW_ON_ERROR));
    }

    /**
     * タグ作成
     *
     * @return \Cake\Http\Response
     */
    public function add(): Response
    {
        $this->request->allowMethod(['post']);
        $tag = $this->Tags->newEmptyEntity();
        $tag = $this->Tags->patchEntity($tag, $this->request->getData());
        if ($this->Tags->save($tag)) {
            return $this->response
                ->withStatus(201)
                ->withType('application/json')
                ->withStringBody((string)json_encode(['tag' => $tag], JSON_THROW_ON_ERROR));
        }

        return $this->response
            ->withStatus(422)
            ->withType('application/json')
            ->withStringBody((string)json_encode(['errors' => $tag->getErrors()], JSON_THROW_ON_ERROR));
    }

    /**
     * タグ削除
     *
     * @param string|null $id Tag id.
     * @return \Cake\Http\Response
     */
    public function delete(?string $id = null): Response
    {
        $this->request->allowMethod(['delete', 'post']);
        $tag = $this->Tags->get((int)$id);
        $this->Tags->delete($tag);

        return $this->response->withStatus(204);
    }
}
