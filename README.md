
# Todo App

CakePHP 5 をバックエンド、Vue 3 をフロントエンドとした Todo 管理アプリです。

## 技術スタック

| レイヤー | 技術 |
|---|---|
| バックエンド | CakePHP 5.3, PHP 8.2+ |
| フロントエンド | Vue 3, Vue Router, Vite |
| スタイル | Tailwind CSS 4 |
| データベース | MySQL (デフォルト) |
| 認証 | CakePHP Authentication プラグイン |

## 機能

- ユーザー登録 / ログイン
- Todo の作成・一覧・詳細・編集・削除

## 画面一覧

| URL | 画面 |
|---|---|
| `/app/todos` | Todo 一覧 |
| `/app/todos/new` | Todo 追加 |
| `/app/todos/:id` | Todo 詳細 |
| `/app/todos/:id/edit` | Todo 編集 |
| `/app/login` | ログイン |
| `/app/register` | ユーザー登録 |

## セットアップ

### 前提条件

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL

### 手順

1. **依存パッケージのインストール**

   ```bash
   composer install
   cd frontend && npm install && cd ..
   ```

2. **環境設定**

   ```bash
   cp config/app_local.example.php config/app_local.php
   ```

   `config/app_local.php` を編集し、データベース接続情報を設定します。

3. **データベースのマイグレーション**

   ```bash
   bin/cake migrations migrate
   ```

4. **フロントエンドのビルド**

   ```bash
   cd frontend && npm run build
   ```

## 開発

### バックエンド (CakePHP)

```bash
bin/cake server -p 8765
```

### フロントエンド (Vue + Vite)

別ターミナルで実行します。Vite の開発サーバーは API リクエストを `localhost:8765` にプロキシします。

```bash
cd frontend && npm run dev
```

ブラウザで `http://localhost:5173/app/` を開きます。

## テスト

### PHPUnit (バックエンド)

```bash
composer test
```

### Playwright E2E テスト

事前準備:

```bash
cd e2e && npm install
npx playwright install chromium
```

実行方法 (別ターミナルで CakePHP サーバーを起動してから):

```bash
# ターミナル 1
bin/cake server -p 8765

# ターミナル 2
cd e2e && npm test
```

テスト一覧:

| ファイル | 件数 | 内容 |
|---|---|---|
| `tests/auth.spec.js` | 5 | ログイン・ログアウト・リダイレクト |
| `tests/todos.spec.js` | 9 | CRUD・フィルター・検索・一括操作 |
| `tests/calendar.spec.js` | 6 | カレンダービュー |
| `tests/stats.spec.js` | 6 | 統計カード |

初回実行時にテストユーザー (`e2etest@example.com`) が自動作成され、ログイン状態が `e2e/.auth/state.json` に保存されます。

## Claude Code 連携 (MCP)

`.mcp.json` に [DBHub](https://github.com/bytebase/dbhub) (MCP サーバー) を設定しています。Claude Code から開発用 DB (`tmp/todo_app.sqlite`) のテーブルやデータを直接調べられます。DBHub の設定は `.dbhub.toml` にあります。

前提条件:

- Node.js 22.5 以上 (DBHub が Node 組み込みの `node:sqlite` を使うため)
- `bin/cake migrations migrate` で DB を作成済みであること

使い方:

1. プロジェクトで Claude Code を起動し、`.mcp.json` のサーバーを使うかの確認で許可する
2. `/mcp` で `dbhub` が connected になっていることを確認する
3. 「未完了の Todo を優先度ごとに数えて」のように頼むと、次のツールが使われる

| ツール | 内容 |
|---|---|
| `execute_sql` | SQL を実行する。**読み取り専用** (SELECT / WITH / EXPLAIN / PRAGMA のみ)、最大 100 行 |
| `search_objects` | テーブル・カラム・インデックスを検索する |
| `overdue_todos` | カスタムツール。期限切れで未完了の Todo を一覧する (`email` で絞り込み可) |

これらのツールは `.claude/settings.json` で許可済みなので、実行時の確認は出ません。

カスタムツールを増やすときは `.dbhub.toml` に `[[tools]]` を追加し、`.claude/settings.json` の `permissions.allow` に `mcp__dbhub__<ツール名>` を追加します。SQLite のプレースホルダーは `?` で、同じ `?` を 2 回書くと 2 つのパラメーターとして数えられます。

## API エンドポイント

| メソッド | パス | 説明 |
|---|---|---|
| POST | `/api/users/register` | ユーザー登録 |
| POST | `/api/users/login` | ログイン |
| POST | `/api/users/logout` | ログアウト |
| GET | `/api/todos` | Todo 一覧取得 |
| POST | `/api/todos` | Todo 作成 |
| GET | `/api/todos/:id` | Todo 取得 |
| PUT | `/api/todos/:id` | Todo 更新 |
| DELETE | `/api/todos/:id` | Todo 削除 |

## プロジェクト構成

### バックエンド (CakePHP)

```
src/
├── Application.php              # アプリ起動・ミドルウェア登録
├── Controller/
│   ├── AppController.php        # 基底コントローラー
│   ├── SpaController.php        # Vue SPA シェルの配信
│   ├── TodosController.php      # サーバーサイド Todo 画面
│   ├── UsersController.php      # ログイン・登録
│   └── Api/
│       └── TodosController.php  # Todo REST API
├── Middleware/
│   ├── ApiUnauthorizedMiddleware.php
│   └── HostHeaderMiddleware.php
├── Model/
│   ├── Entity/                  # Todo.php, User.php
│   └── Table/                   # TodosTable.php, UsersTable.php
└── View/
    └── AppView.php

config/
├── app.php                      # メイン設定
├── app_local.php                # 環境別ローカル設定
├── routes.php                   # ルーティング定義
├── bootstrap.php
└── Migrations/                  # DB マイグレーション

templates/
├── layout/
│   ├── default.php
│   └── spa.php                  # Vue SPA を読み込むレイアウト
├── Spa/index.php                # SPA エントリーテンプレート
├── Todos/                       # サーバーレンダリング Todo 画面
└── Users/                       # ログイン・登録画面
```

### フロントエンド (Vue.js)

```
frontend/src/
├── main.js                      # Vue アプリエントリーポイント
├── App.vue                      # ルートコンポーネント
├── api.js                       # API クライアント
├── router/index.js              # クライアントサイドルーティング
├── views/
│   ├── LoginView.vue
│   ├── RegisterView.vue
│   ├── TodoListView.vue
│   ├── TodoDetailView.vue
│   └── TodoFormView.vue
├── layouts/
│   └── AppLayout.vue
└── components/
    └── HelloWorld.vue
```

### その他のディレクトリ

| ディレクトリ | 用途 |
|---|---|
| `webroot/` | Web 公開ディレクトリ（フロントエンドビルド出力先: `webroot/app/`） |
| `tests/` | PHPUnit テストケース・フィクスチャ |
| `vendor/` | Composer (PHP) 依存パッケージ |
| `node_modules/` | NPM 依存パッケージ |
| `css/` | Tailwind CSS 入力ファイル |
| `tmp/` | キャッシュ・セッション・ログ |
| `plugins/` | CakePHP プラグイン |
| `docs/` | ドキュメント（`ARCHITECTURE.md` など） |
