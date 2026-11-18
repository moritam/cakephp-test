# CLAUDE.md

このファイルは Claude Code がこのリポジトリで作業する際のガイドラインです。

## プロジェクト概要

CakePHP 5 (バックエンド) + Vue 3 (フロントエンド SPA) による Todo 管理アプリ。

## 技術スタック

| レイヤー | 技術 |
|---|---|
| バックエンド | CakePHP 5.3, PHP 8.2+ |
| フロントエンド | Vue 3, Vue Router, Vite |
| スタイル | Tailwind CSS 4 |
| データベース | SQLite (`tmp/todo_app.sqlite`、テストは `tmp/tests.sqlite`) |
| 認証 | CakePHP Authentication プラグイン |

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
│   ├── UsersController.php      # ログイン・ログアウト・ユーザー登録 (JSON / HTML 両対応)
│   ├── TodosController.php      # 旧サーバーサイドレンダリング版 Todo 画面 (SPA では未使用)
│   ├── PagesController.php      # CakePHP 標準 (静的ページ)
│   ├── ErrorController.php      # CakePHP 標準 (エラー画面)
│   └── Api/
│       └── TodosController.php  # Todo REST API (JSON)
├── Middleware/
│   ├── ApiUnauthorizedMiddleware.php  # /api への未認証アクセスに 401 JSON を返す
│   └── HostHeaderMiddleware.php       # 本番環境での Host ヘッダー検証
└── Model/
    ├── Entity/                  # Todo.php, User.php (パスワードは自動ハッシュ化・JSON 非公開)
    └── Table/                   # TodosTable.php, UsersTable.php (バリデーション定義)

config/
├── routes.php                   # ルーティング定義
├── app.php / app_local.php      # アプリ設定 / 環境ごとの設定 (DB 接続は app_local.php で SQLite を指定)
├── plugins.php                  # 読み込むプラグイン
└── Migrations/                  # DB マイグレーション (CreateTodos, CreateUsers)

templates/
├── layout/spa.php               # Vue SPA を読み込むレイアウト (/app/assets/index.js)
├── Spa/index.php                # SPA エントリーテンプレート (空)
└── Todos/, Users/, layout/default.php, element/flash/  # 旧サーバーサイド画面用

webroot/
└── app/                         # frontend のビルド成果物 (直接編集しない)

tests/
├── Fixture/TodosFixture.php
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
    ├── TodoListView.vue
    ├── TodoDetailView.vue
    └── TodoFormView.vue         # 追加・編集で共用 (route meta.mode で切替)

frontend/vite.config.js          # base: /app/、ビルド出力先、開発用プロキシ
```

## API エンドポイント

| メソッド | パス | 説明 |
|---|---|---|
| POST | `/users/add` | ユーザー登録 |
| POST | `/users/login` | ログイン |
| POST | `/users/logout` | ログアウト |
| GET | `/api/todos` | Todo 一覧取得 (更新日時の降順、最大 100 件) |
| POST | `/api/todos` | Todo 作成 |
| GET | `/api/todos/:id` | Todo 取得 |
| PUT / PATCH | `/api/todos/:id` | Todo 更新 |
| DELETE | `/api/todos/:id` | Todo 削除 (204) |

- ユーザー系は `/api` プレフィックスなしで `UsersController` が処理する。`Accept: application/json` のときは JSON、それ以外は HTML を返す。
- `/api/*` はログインが必要。未認証のときは 401 `{"error":"Unauthorized"}` を返す。
- バリデーションエラーのときは 422 `{"errors": {...}}` を返す。
- JSON リクエストでは `/api/*` と `/users/{login,logout,add}` の CSRF チェックを行わない (`Application::middleware()`)。

## 画面 URL

| URL | 画面 |
|---|---|
| `/app/todos` | Todo 一覧 |
| `/app/todos/new` | Todo 追加 |
| `/app/todos/:id` | Todo 詳細 |
| `/app/todos/:id/edit` | Todo 編集 |
| `/app/login` | ログイン |
| `/app/register` | ユーザー登録 |

## コーディング規約

- PHP: PSR-12 準拠 (phpcs.xml で設定済み)
- フロントエンド: Vue 3 Composition API を使用
- API レスポンスは JSON 形式

## 既知の注意点

- `GET /api/todos` のレスポンスの `pagination.count` が常に 0 になる (`paging` リクエスト属性を参照しているため)。
- `PagesControllerTest` の 4 件が失敗する (認証の導入で `/pages/*` が 302 リダイレクトになったが、テストが未更新)。
- `users` と `todos` は関連付いていない (`todos.user_id` なし)。ログインしたユーザーは全員、すべての Todo を参照・編集できる。
- 旧サーバーサイド画面 (`src/Controller/TodosController.php`、`templates/Todos/` など) が残っている。`fallbacks()` があるため `/todos` などでもアクセスできる。
- `UsersController::dbTest` はデバッグ用のアクションで、認証なしでアクセスできる。