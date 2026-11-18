<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * Users Controller (JSON)
 *
 * Vue SPA から呼ばれるログイン・ログアウト・ユーザー登録の API。
 * 画面は SPA 側にあるため、ブラウザで直接開かれた場合は SPA の画面へリダイレクトする。
 *
 * @property \App\Model\Table\UsersTable $Users
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 */
class UsersController extends AppController
{
    /**
     * 認証不要のアクション (ログアウトは未ログインでも成功扱いにする)
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authentication->allowUnauthenticated(['login', 'logout', 'add']);
    }

    /**
     * ログイン (POST)
     *
     * 認証自体は Authentication ミドルウェアの Form 認証が行い、ここでは結果を返す。
     *
     * @return \Cake\Http\Response
     */
    public function login(): Response
    {
        if (!$this->request->is('post')) {
            return $this->redirect('/app/login');
        }

        $result = $this->Authentication->getResult();
        if ($result && $result->isValid()) {
            return $this->json(['success' => true]);
        }

        return $this->json([
            'success' => false,
            'message' => __('メールアドレスまたはパスワードが正しくありません。'),
        ], 401);
    }

    /**
     * ログアウト
     *
     * @return \Cake\Http\Response
     */
    public function logout(): Response
    {
        $this->Authentication->logout();

        if (!$this->request->is('post')) {
            return $this->redirect('/app/login');
        }

        return $this->json(['success' => true]);
    }

    /**
     * ユーザー登録 (POST)
     *
     * @return \Cake\Http\Response
     */
    public function add(): Response
    {
        if (!$this->request->is('post')) {
            return $this->redirect('/app/register');
        }

        $user = $this->Users->newEmptyEntity();
        $user = $this->Users->patchEntity($user, $this->request->getData());
        if ($this->Users->save($user)) {
            return $this->json(['success' => true], 201);
        }

        return $this->json([
            'success' => false,
            'errors' => $user->getErrors(),
        ], 422);
    }

    /**
     * JSON レスポンスを作る
     *
     * @param array<string, mixed> $data レスポンスボディ
     * @param int $status HTTP ステータス
     * @return \Cake\Http\Response
     */
    private function json(array $data, int $status = 200): Response
    {
        return $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withStringBody((string)json_encode($data, JSON_THROW_ON_ERROR));
    }
}
