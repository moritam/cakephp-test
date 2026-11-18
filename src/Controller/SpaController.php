<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * SPA 用コントローラ（Vue アプリの HTML を返す）
 */
class SpaController extends AppController
{
    /**
     * 認証不要
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authentication->allowUnauthenticated(['index', 'redirectToApp']);
    }

    /**
     * ルート / を /app へリダイレクト
     *
     * @return \Cake\Http\Response
     */
    public function redirectToApp(): Response
    {
        return $this->redirect('/app');
    }

    /**
     * Vue SPA 用の HTML を返す
     *
     * @return \Cake\Http\Response|null
     */
    public function index(): ?Response
    {
        $this->viewBuilder()->setLayout('spa');
        $this->viewBuilder()->setTemplate('index');

        return null;
    }
}
