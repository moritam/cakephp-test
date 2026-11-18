# CLAUDE.md

このファイルは Claude Code がこのリポジトリで作業する際のガイドラインです。

## プロジェクト概要

CakePHP 5 (バックエンド) + Vue 3 (フロントエンド SPA) による Todo 管理アプリ。

### 主な機能

- ユーザー登録・ログイン (セッション認証、CSRF 保護)
- Todo の CRUD (タイトル・説明・優先度・期限日)
- タグ管理 (カラーピッカー付き) と Todo への多重タグ付け
- 一覧の検索 (タイトルキーワード)・フィルター (優先度/完了状態/タグ)・ソート (更新日/期限日/優先度)
- 期限切れ Todo の赤ハイライト表示
- ダークモード切り替え (localStorage 永続化)

## 技術スタック

| レイヤー | 技術 |
|---|---|
| バックエンド | CakePHP 5.3, PHP 8.2+ |
| フロントエンド | Vue 3, Vue Router, Vite |
| スタイル | Tailwind CSS 4 |
| データベース | SQLite (`tmp/todo_app.sqlite`、テストは `tmp/tests.sqlite`) |
| 認証 | CakePHP Authentication プラグイン |

## 初回セットアップ

```bash
composer install            # config/app_local.example.php から config/app_local.php を生成し、Security.salt をランダムに設定
bin/cake migrations migrate # tmp/todo_app.sqlite を作成
cd frontend && npm install
```

- `config/app_local.php` と `config/.env` は秘密情報を含むため Git 管理外 (`.gitignore`)。設定項目を増やすときは `config/app_local.example.php` も更新する。
- 本番環境では環境変数で上書きする: `SECURITY_SALT`、`DATABASE_URL`、`DEBUG=false`、`APP_FULL_BASE_URL`。必要に応じて `COOKIE_SECURE` (未指定なら debug=false のとき true)、`SESSION_TIMEOUT` (分、既定 120)、`CACHE_RATELIMIT_URL`。
- Cookie: セッションは `TODOAPP_SESSION` (HttpOnly、SameSite=Lax、strict mode)、CSRF は `csrfToken` (HttpOnly、SameSite=Lax)。`Secure` 属性は両方とも `App.secureCookies` (config/bootstrap.php) に従う。ログイン・ログアウト時にセッション ID は作り直される (Authentication プラグイン)。

## 開発サーバーの起動

```bash
# バックエンド (ポート 8765)
bin/cake server -p 8765

# フロントエンド (ポート 5173、別ターミナルで実行)
cd frontend && npm run dev
```

ブラウザ: `http://localhost:5173/app/`

## よく使うコマンド

```bash
# PHP コードスタイルチェック
composer cs-check

# PHP コードスタイル自動修正
composer cs-fix

# PHPUnit テスト
composer test

# DB マイグレーション (初回はこれで tmp/todo_app.sqlite が作成される)
bin/cake migrations migrate

# フロントエンドビルド (出力先: webroot/app/)
cd frontend && npm run build

# PHP テンプレート用 Tailwind CSS ビルド (css/input.css → webroot/css/tailwind.css)
npm run build
```

開発時は Vite (5173) が `/api`・`/users`・`/css` を CakePHP (8765) へプロキシする (`frontend/vite.config.js`)。
本番相当では `frontend` をビルドし、CakePHP の `/app` から配信する。

## プロジェクト構成

### バックエンド (CakePHP)

```
src/
├── Application.php              # ミドルウェア構成・認証サービス設定 (email/password, セッション)
├── Controller/
│   ├── AppController.php        # 基底コントローラー (Flash, Authentication を読み込み)
│   ├── SpaController.php        # Vue SPA シェルの配信 (/app, /app/*)、/ → /app リダイレクト
│   ├── UsersController.php      # ログイン・ログアウト・ユーザー登録 (JSON API)
│   ├── ErrorController.php      # CakePHP 標準 (エラー画面)
│   └── Api/
│       ├── TodosController.php  # Todo REST API (JSON)。検索・フィルター・ソート対応
│       └── TagsController.php   # Tag REST API (JSON)
├── Middleware/
│   ├── ApiUnauthorizedMiddleware.php  # /api への未認証アクセスに 401 JSON を返す
│   ├── LoginThrottleMiddleware.php    # ログイン失敗回数の制限 (超過で 429)
│   └── HostHeaderMiddleware.php       # 本番環境での Host ヘッダー検証
└── Model/
    ├── Entity/                  # Todo.php, User.php, Tag.php
    └── Table/                   # TodosTable.php (findOwnedBy/findFiltered), UsersTable.php, TagsTable.php

config/
├── routes.php                   # ルーティング定義
├── app.php                      # アプリ共通設定
├── app_local.php                # 環境ごとの設定・秘密情報 (Git 管理外。app_local.example.php から生成)
├── plugins.php                  # 読み込むプラグイン
└── Migrations/                  # DB マイグレーション (CreateTodos, CreateUsers, AddUserIdToTodos, AddFieldsToTodos, CreateTags, CreateTagsTodos)

templates/
├── layout/spa.php               # Vue SPA を読み込むレイアウト (/app/assets/index.js)
├── Spa/index.php                # SPA エントリーテンプレート (空)
└── Error/, layout/error.php     # エラー画面

webroot/
└── app/                         # frontend のビルド成果物 (直接編集しない)

tests/
├── Fixture/                     # TodosFixture.php, UsersFixture.php
└── TestCase/                    # PHPUnit テスト
```

### フロントエンド (Vue.js)

```
frontend/src/
├── main.js                      # Vue アプリエントリーポイント
├── App.vue                      # ルートコンポーネント
├── api.js                       # API クライアント (fetch, Cookie セッション送信)
├── style.css                    # Tailwind 読み込み
├── router/index.js              # クライアントサイドルーティング + 認証ガード
├── layouts/
│   └── AppLayout.vue            # ログイン後の共通レイアウト (ナビ・ログアウト)
├── components/HelloWorld.vue    # Vite 雛形の残り (未使用)
└── views/
    ├── LoginView.vue
    ├── RegisterView.vue
    ├── TodoListView.vue         # 検索・フィルター・ソート・優先度バッジ・期限切れハイライト
    ├── TodoDetailView.vue
    ├── TodoFormView.vue         # 追加・編集で共用 (route meta.mode で切替)。優先度/期限日/説明/タグ対応
    └── TagListView.vue          # タグ管理 (作成・削除)

frontend/vite.config.js          # base: /app/、ビルド出力先、開発用プロキシ
```

## API エンドポイント

| メソッド | パス | 説明 |
|---|---|---|
| POST | `/users/add` | ユーザー登録 |
| POST | `/users/login` | ログイン |
| POST | `/users/logout` | ログアウト |
| GET | `/users/csrf-token` | CSRF トークン取得 |
| GET | `/api/todos` | Todo 一覧取得 (最大 100 件)。クエリパラメータ: `q`, `priority`, `completed`, `tag_id`, `overdue`, `sort`, `direction` |
| POST | `/api/todos` | Todo 作成 |
| GET | `/api/todos/:id` | Todo 取得 (タグ含む) |
| PUT / PATCH | `/api/todos/:id` | Todo 更新 |
| DELETE | `/api/todos/:id` | Todo 削除 (204) |
| GET | `/api/tags` | タグ一覧取得 |
| POST | `/api/tags` | タグ作成 |
| DELETE | `/api/tags/:id` | タグ削除 (204) |

- ユーザー系は `/api` プレフィックスなしで `UsersController` が処理し、常に JSON を返す。ブラウザで直接 GET した場合は SPA の画面 (`/app/login`、`/app/register`) へリダイレクトする。
- ルートは `config/routes.php` に明示したものだけを公開する (`fallbacks()` は使わない)。
- `/api/*` はログインが必要。未認証のときは 401 `{"error":"Unauthorized"}` を返す。
- バリデーションエラーのときは 422 `{"errors": {...}}` を返す。
- ログインの失敗 (401) は `LoginThrottleMiddleware` が数え、15 分間に「同じ IP + メールアドレス」で 5 回、「同じ IP」で 20 回を超えると 429 (`Retry-After` 付き) を返す。成功すると「IP + メールアドレス」のカウントは 0 に戻る。カウントは Cache 設定 `rate_limit` に保存する (`bin/cake cache clear rate_limit` で解除)。IP は `REMOTE_ADDR` を使う (リバースプロキシ配下では trustProxy の設定が必要)。
- POST / PUT / PATCH / DELETE はすべて CSRF トークンが必要 (例外なし)。トークンは `GET /users/csrf-token` で取得し、`X-CSRF-Token` ヘッダーで送る。`frontend/src/api.js` が自動で付与し、403 のときは取り直して 1 回だけ再送する。
- PHP のテストで JSON を POST する場合は `enableCsrfToken()` が使えないので、`CsrfProtectionMiddleware::createToken()` で作ったトークンを `csrfToken` Cookie と `X-CSRF-Token` ヘッダーの両方に設定する (`tests/TestCase/Controller/Api/TodosControllerTest.php` の `setUp()` 参照)。

## 画面 URL

| URL | 画面 |
|---|---|
| `/app/todos` | Todo 一覧 |
| `/app/todos/new` | Todo 追加 |
| `/app/todos/:id` | Todo 詳細 |
| `/app/todos/:id/edit` | Todo 編集 |
| `/app/tags` | タグ管理 |
| `/app/login` | ログイン |
| `/app/register` | ユーザー登録 |

## コーディング規約

- PHP: PSR-12 準拠 (phpcs.xml で設定済み)
- フロントエンド: Vue 3 Composition API を使用
- API レスポンスは JSON 形式
- Todo はユーザーごとに分離する。`Api\TodosController` では `find('ownedBy', userId: ...)` でログインユーザーの Todo に絞り込み、他ユーザーの Todo は 404 を返す。`user_id` はリクエストから受け取らず、コントローラーで設定する
- タグはユーザー共通 (全ユーザーで同じタグプールを共有する設計)
- Todo の `priority` は `'high'|'medium'|'low'` の文字列。デフォルトは `'medium'`
- Tailwind CSS 4 のダークモードは `@custom-variant dark (&:where(.dark, .dark *))` で `class` 戦略を設定している。`html` 要素への `.dark` クラス付与は `AppLayout.vue` が行う
- Todo と Tag は `BelongsToMany` で結合テーブル `tags_todos` を使う (CakePHP 規約: アルファベット順 tags < todos)

## 既知の注意点

- 新しいマイグレーションを追加した後に PHPUnit がブートストラップで失敗する場合は、`tmp/tests.sqlite` を削除して再実行する (cakephp/migrations 5.0.1 の Migrator は、テーブルを削除しても `cake_migrations` の適用履歴を消さないため)。
- `FixedWindowRateLimiter::reset()` (cakephp 5.3) は時間枠を 1 時間と決め打ちしているため使わない。`LoginThrottleMiddleware::resetCounter()` で同じキー規則を使って削除している。
- `Configure::write()` のドット記法では `session.cookie_secure` のような "." を含む ini キーを書けない (入れ子の配列になり、Session が "setting session failed" で落ちる)。`Session.ini` を配列ごと読み書きする。
- PHP のテストで `configRequest()` を繰り返し呼ぶと `environment` が再帰マージされ、`REMOTE_ADDR` が配列になる。IP を変えるテストでは毎回 `$this->_request = []` で初期化する。
- `templates/layout/default.php`、`templates/element/flash/`、`webroot/css/` の CakePHP 標準 CSS は、旧画面の削除後は使われていない。