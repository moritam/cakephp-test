<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 */
class UsersController extends AppController
{
    /**
     * 認証不要のアクション
     */
    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authentication->allowUnauthenticated(['login', 'add', 'dbTest']);
    }

    /**
     * DB接続テスト用（動作確認後に削除してOK）
     */
    public function dbTest()
    {
        try {
            $this->Users->find()->first();
            $this->Flash->success('DB接続成功');
        } catch (\Throwable $e) {
            $this->Flash->error('DB接続エラー: ' . $e->getMessage());
        }

        return $this->redirect(['action' => 'login']);
    }

    /**
     * ログイン
     *
     * @return \Cake\Http\Response|null|void
     */
    public function login()
    {
        $result = $this->Authentication->getResult();

        if ($result && $result->isValid()) {
            if ($this->request->is('json') || $this->request->accepts('application/json')) {
                return $this->response
                    ->withType('application/json')
                    ->withStringBody((string)json_encode(['success' => true], JSON_THROW_ON_ERROR));
            }
            $target = $this->Authentication->getLoginRedirect() ?? [
                'controller' => 'Todos',
                'action' => 'index',
            ];
            return $this->redirect($target);
        }

        if ($this->request->is('post')) {
            if ($this->request->is('json') || $this->request->accepts('application/json')) {
                return $this->response
                    ->withStatus(401)
                    ->withType('application/json')
                    ->withStringBody((string)json_encode([
                        'success' => false,
                        'message' => __('メールアドレスまたはパスワードが正しくありません。'),
                    ], JSON_THROW_ON_ERROR));
            }
            $this->Flash->error(__('メールアドレスまたはパスワードが正しくありません。'));
        }
    }

    /**
     * ログアウト
     *
     * @return \Cake\Http\Response|null
     */
    public function logout()
    {
        $this->Authentication->logout();

        if ($this->request->is('json') || $this->request->accepts('application/json')) {
            return $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode(['success' => true], JSON_THROW_ON_ERROR));
        }
        return $this->redirect(['controller' => 'Users', 'action' => 'login']);
    }

    /**
     * ユーザー登録
     *
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        $user = $this->Users->newEmptyEntity();

        if ($this->request->is('post')) {
            $user = $this->Users->patchEntity($user, $this->request->getData());

            if ($this->Users->save($user)) {
                if ($this->request->is('json') || $this->request->accepts('application/json')) {
                    return $this->response
                        ->withStatus(201)
                        ->withType('application/json')
                        ->withStringBody((string)json_encode(['success' => true], JSON_THROW_ON_ERROR));
                }
                $this->Flash->success(__('ユーザーを登録しました。ログインしてください。'));
                return $this->redirect(['action' => 'login']);
            }

            if ($this->request->is('json') || $this->request->accepts('application/json')) {
                return $this->response
                    ->withStatus(422)
                    ->withType('application/json')
                    ->withStringBody((string)json_encode([
                        'success' => false,
                        'errors' => $user->getErrors(),
                    ], JSON_THROW_ON_ERROR));
            }
            $this->Flash->error(__('ユーザーの登録に失敗しました。もう一度お試しください。'));
        }

        $this->set(compact('user'));
    }
}
