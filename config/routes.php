<?php
/**
 * Routes configuration.
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * It's loaded within the context of `Application::routes()` method which
 * receives a `RouteBuilder` instance `$routes` as method argument.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

/*
 * This file is loaded in the context of the `Application` class.
 * So you can use `$this` to reference the application class instance
 * if required.
 */
return function (RouteBuilder $routes): void {
    /*
     * The default class to use for all routes
     *
     * The following route classes are supplied with CakePHP and are appropriate
     * to set as the default:
     *
     * - Route
     * - InflectedRoute
     * - DashedRoute
     *
     * If no call is made to `Router::defaultRouteClass()`, the class used is
     * `Route` (`Cake\Routing\Route\Route`)
     *
     * Note that `Route` does not do any inflections on URLs which will result in
     * inconsistently cased URLs when used with `{plugin}`, `{controller}` and
     * `{action}` markers.
     */
    $routes->setRouteClass(DashedRoute::class);

    // API (JSON) routes for Vue SPA
    $routes->scope('/api', function (RouteBuilder $builder): void {
        $builder->setRouteClass(DashedRoute::class);
        $builder->get('/todos', ['controller' => 'Todos', 'action' => 'index', 'prefix' => 'Api']);
        $builder->get('/todos/{id}', ['controller' => 'Todos', 'action' => 'view', 'prefix' => 'Api'])
            ->setPass(['id']);
        $builder->post('/todos', ['controller' => 'Todos', 'action' => 'add', 'prefix' => 'Api']);
        $builder->put('/todos/{id}', ['controller' => 'Todos', 'action' => 'edit', 'prefix' => 'Api'])
            ->setPass(['id']);
        $builder->patch('/todos/{id}', ['controller' => 'Todos', 'action' => 'edit', 'prefix' => 'Api'])
            ->setPass(['id']);
        $builder->delete('/todos/{id}', ['controller' => 'Todos', 'action' => 'delete', 'prefix' => 'Api'])
            ->setPass(['id']);

        // Tags
        $builder->get('/tags', ['controller' => 'Tags', 'action' => 'index', 'prefix' => 'Api']);
        $builder->post('/tags', ['controller' => 'Tags', 'action' => 'add', 'prefix' => 'Api']);
        $builder->delete('/tags/{id}', ['controller' => 'Tags', 'action' => 'delete', 'prefix' => 'Api'])
            ->setPass(['id']);
    });

    $routes->scope('/', function (RouteBuilder $builder): void {
        // ルートは /app へリダイレクト
        $builder->connect('/', ['controller' => 'Spa', 'action' => 'redirectToApp']);
        // SPA: /app および /app/* で Vue アプリを配信
        $builder->connect('/app', ['controller' => 'Spa', 'action' => 'index']);
        $builder->connect('/app/*', ['controller' => 'Spa', 'action' => 'index']);

        // 認証・ユーザー登録 (JSON API)
        $builder->connect('/users/login', ['controller' => 'Users', 'action' => 'login']);
        $builder->connect('/users/logout', ['controller' => 'Users', 'action' => 'logout']);
        $builder->connect('/users/add', ['controller' => 'Users', 'action' => 'add']);
        $builder->get('/users/csrf-token', ['controller' => 'Users', 'action' => 'csrfToken']);

        // fallbacks() は使わない: 定義したルート以外のコントローラー・アクションは公開しない
    });
};
