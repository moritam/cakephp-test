# Todo App アーキテクチャ構成図

CakePHP 5 と Authentication プラグインを使用した TODO アプリケーションの構成図です。

---

## 1. 全体アーキテクチャ

```mermaid
%%{init: {'theme':'base', 'themeVariables': { 'primaryColor':'#0f172a', 'primaryTextColor':'#f8fafc', 'primaryBorderColor':'#334155', 'lineColor':'#64748b', 'secondaryColor':'#1e293b', 'secondaryTextColor':'#f8fafc', 'tertiaryColor':'#334155', 'tertiaryTextColor':'#f8fafc', 'background':'#020617', 'mainBkg':'#0f172a', 'secondBkg':'#1e293b', 'tertiaryBkg':'#334155', 'border1':'#475569', 'border2':'#64748b', 'arrowheadColor':'#94a3b8', 'fontFamily':'system-ui, sans-serif'}}}%%
flowchart TB
    subgraph Client["🖥️ クライアント"]
        Browser["Webブラウザ"]
    end

    subgraph Middleware["⚙️ ミドルウェア層"]
        Error["ErrorHandler"]
        Host["HostHeader"]
        Asset["Asset"]
        Routing["Routing"]
        BodyParser["BodyParser"]
        Auth["Authentication"]
        CSRF["CSRF"]
    end

    subgraph Application["📦 アプリケーション層"]
        AppController["AppController"]
        TodosController["TodosController"]
        UsersController["UsersController"]
    end

    subgraph Model["🗃️ モデル層"]
        TodosTable["TodosTable"]
        UsersTable["UsersTable"]
    end

    subgraph View["🖼️ ビュー層"]
        Layout["layout"]
        TodosViews["Todos Templates"]
        UsersViews["Users Templates"]
    end

    subgraph Data["💾 データ層"]
        MySQL[("MySQL")]
    end

    Browser --> Error --> Host --> Asset --> Routing --> BodyParser --> Auth --> CSRF
    CSRF --> AppController
    AppController --> TodosController
    AppController --> UsersController
    TodosController --> TodosTable
    UsersController --> UsersTable
    TodosTable --> MySQL
    UsersTable --> MySQL
    TodosController --> TodosViews
    UsersController --> UsersViews
    TodosViews --> Layout
    UsersViews --> Layout
    Layout --> Browser
```

---

## 2. MVC 構造

```mermaid
%%{init: {'theme':'base', 'themeVariables': { 'primaryColor':'#6366f1', 'primaryTextColor':'#fff', 'primaryBorderColor':'#4f46e5', 'lineColor':'#818cf8', 'secondaryColor':'#8b5cf6', 'secondaryTextColor':'#fff', 'tertiaryColor':'#a78bfa', 'tertiaryTextColor':'#fff', 'background':'#faf5ff', 'mainBkg':'#6366f1', 'secondBkg':'#8b5cf6', 'tertiaryBkg':'#a78bfa', 'fontFamily':'system-ui, sans-serif'}}}%%
flowchart LR
    subgraph Controllers["🎮 Controller"]
        AC["AppController"]
        TC["TodosController"]
        UC["UsersController"]
    end

    subgraph Models["📊 Model"]
        TT["TodosTable"]
        TE["Todo Entity"]
        UT["UsersTable"]
        UE["User Entity"]
    end

    subgraph Views["🎨 View"]
        Tidx["index"]
        Tadd["add"]
        Tedit["edit"]
        Tview["view"]
        Ulogin["login"]
        Uadd["add"]
    end

    AC --> TC
    AC --> UC
    TC --> TT
    TC --> TE
    UC --> UT
    UC --> UE
    TT --> TE
    UT --> UE
    TC --> Tidx
    TC --> Tadd
    TC --> Tedit
    TC --> Tview
    UC --> Ulogin
    UC --> Uadd
```

---

## 3. ディレクトリ構成

```mermaid
%%{init: {'theme':'base', 'themeVariables': { 'primaryColor':'#059669', 'primaryTextColor':'#fff', 'primaryBorderColor':'#047857', 'lineColor':'#34d399', 'secondaryColor':'#0d9488', 'secondaryTextColor':'#fff', 'tertiaryColor':'#14b8a6', 'tertiaryTextColor':'#fff', 'background':'#f0fdf4', 'mainBkg':'#059669', 'secondBkg':'#0d9488', 'tertiaryBkg':'#14b8a6', 'fontFamily':'ui-monospace, monospace'}}}%%
flowchart TD
    subgraph src["📁 src"]
        App["Application.php"]
        subgraph Controller["Controller"]
            AppC["AppController.php"]
            TodosC["TodosController.php"]
            UsersC["UsersController.php"]
        end
        subgraph Model["Model"]
            TodosT["TodosTable.php"]
            UsersT["UsersTable.php"]
            TodoE["Todo.php"]
            UserE["User.php"]
        end
    end
    subgraph config["⚙️ config"]
        Routes["routes.php"]
        AppLocal["app_local.php"]
    end
    subgraph templates["📄 templates"]
        Layout["layout default"]
        Tidx["Todos"]
        Ulogin["Users"]
        Flash["element flash"]
    end
    subgraph webroot["🌐 webroot"]
        CSS["tailwind.css"]
    end
    subgraph css["🎨 css"]
        Input["input.css"]
    end
```

---

## 4. ルーティング

```mermaid
%%{init: {'theme':'base', 'themeVariables': { 'primaryColor':'#dc2626', 'primaryTextColor':'#fff', 'primaryBorderColor':'#b91c1c', 'lineColor':'#f87171', 'secondaryColor':'#ea580c', 'secondaryTextColor':'#fff', 'tertiaryColor':'#f97316', 'tertiaryTextColor':'#fff', 'background':'#fff7ed', 'mainBkg':'#dc2626', 'secondBkg':'#ea580c', 'tertiaryBkg':'#f97316', 'fontFamily':'system-ui, sans-serif'}}}%%
flowchart LR
    subgraph Routes["🛣️ ルート定義"]
        R1["/"]
        R2["/todos"]
        R3["/todos/add"]
        R4["/todos/view/:id"]
        R5["/todos/edit/:id"]
        R6["/todos/delete/:id"]
        R7["/users/login"]
        R8["/users/add"]
        R9["/users/logout"]
    end

    R1 --> TC["TodosController"]
    R2 --> TC
    R3 --> TC
    R4 --> TC
    R5 --> TC
    R6 --> TC
    R7 --> UC["UsersController"]
    R8 --> UC
    R9 --> UC
```

---

## 5. 認証フロー

```mermaid
%%{init: {'theme':'base', 'themeVariables': { 'primaryColor':'#7c3aed', 'primaryTextColor':'#fff', 'primaryBorderColor':'#6d28d9', 'lineColor':'#a78bfa', 'secondaryColor':'#9333ea', 'secondaryTextColor':'#fff', 'tertiaryColor':'#a855f7', 'tertiaryTextColor':'#fff', 'background':'#faf5ff', 'mainBkg':'#7c3aed', 'secondBkg':'#9333ea', 'actorBkg':'#7c3aed', 'actorTextColor':'#fff', 'actorLineColor':'#6d28d9', 'signalColor':'#fff', 'signalTextColor':'#7c3aed', 'labelBoxBkgColor':'#ede9fe', 'labelTextColor':'#5b21b6', 'loopTextColor':'#fff', 'noteBorderColor':'#a78bfa', 'noteBkgColor':'#ede9fe', 'activationBorderColor':'#7c3aed', 'activationBkgColor':'#ddd6fe', 'sequenceNumberColor':'#fff', 'fontFamily':'system-ui, sans-serif'}}}%%
sequenceDiagram
    participant User as 👤 ユーザー
    participant Browser as 🌐 ブラウザ
    participant Auth as 🔐 Auth
    participant App as 📱 Application
    participant UC as UsersController
    participant TC as TodosController

    User->>Browser: ページアクセス
    Browser->>Auth: HTTPリクエスト
    Auth->>Auth: セッション確認
    
    alt 未認証 認証不要
        Auth->>UC: アクセス許可
        UC->>Browser: ログイン登録画面
    else 未認証 認証必要
        Auth->>Browser: ログインへリダイレクト
    else 認証済み
        Auth->>TC: アクセス許可
        TC->>Browser: Todo一覧を表示
    end

    Note over User,TC: ログイン時
    User->>Browser: email password 入力
    Browser->>App: POST login
    App->>Auth: Form認証
    Auth->>Auth: パスワード検証
    Auth->>Auth: セッション保存
    Auth->>Browser: Todos へリダイレクト
```

---

## 6. データベーススキーマ

```mermaid
%%{init: {'theme':'base', 'themeVariables': { 'primaryColor':'#0e7490', 'primaryTextColor':'#fff', 'primaryBorderColor':'#0d9488', 'lineColor':'#22d3ee', 'secondaryColor':'#0891b2', 'secondaryTextColor':'#fff', 'tertiaryColor':'#06b6d4', 'tertiaryTextColor':'#fff', 'background':'#ecfeff', 'mainBkg':'#0e7490', 'secondBkg':'#0891b2', 'tertiaryBkg':'#06b6d4', 'fontFamily':'system-ui, sans-serif'}}}%%
erDiagram
    users {
        int id PK
        string email UK
        string password
        datetime created
        datetime modified
    }

    todos {
        int id PK
        string title
        boolean completed
        datetime created
        datetime modified
    }

    note right of users : users と todos は現時点で関連なし
```

---

## 7. リクエスト処理フロー（Todo CRUD）

```mermaid
%%{init: {'theme':'base', 'themeVariables': { 'primaryColor':'#1e40af', 'primaryTextColor':'#fff', 'primaryBorderColor':'#1d4ed8', 'lineColor':'#60a5fa', 'secondaryColor':'#2563eb', 'secondaryTextColor':'#fff', 'tertiaryColor':'#3b82f6', 'tertiaryTextColor':'#fff', 'background':'#eff6ff', 'mainBkg':'#1e40af', 'secondBkg':'#2563eb', 'tertiaryBkg':'#3b82f6', 'fontFamily':'system-ui, sans-serif'}}}%%
flowchart TD
    Start([HTTP リクエスト]) --> Auth{認証チェック}
    Auth -->|NG| Login[ログインへ]
    Auth -->|OK| Route{ルーティング}
    
    Route -->|todos| Index[index]
    Route -->|add| Add[add]
    Route -->|view| View[view]
    Route -->|edit| Edit[edit]
    Route -->|delete| Delete[delete]
    
    Index --> Find[find]
    Add --> NewEntity[newEmptyEntity]
    Add --> PatchEntity[patchEntity]
    Add --> Save[save]
    View --> Get[get]
    Edit --> Get
    Edit --> PatchEntity
    Edit --> Save
    Delete --> Get
    Delete --> DeleteRecord[delete]
    
    Find --> MySQL[(MySQL)]
    NewEntity --> MySQL
    Get --> MySQL
    Save --> MySQL
    DeleteRecord --> MySQL
    
    Index --> RenderIndex[index.php]
    Add --> RenderAdd[add.php]
    View --> RenderView[view.php]
    Edit --> RenderEdit[edit.php]
    
    RenderIndex --> Response([HTTP レスポンス])
    RenderAdd --> Response
    RenderView --> Response
    RenderEdit --> Response
    Save --> Redirect[リダイレクト]
    DeleteRecord --> Redirect
    Redirect --> Response
```

---

## 8. コンポーネント・プラグイン

```mermaid
%%{init: {'theme':'base', 'themeVariables': { 'primaryColor':'#be185d', 'primaryTextColor':'#fff', 'primaryBorderColor':'#9d174d', 'lineColor':'#f472b6', 'secondaryColor':'#db2777', 'secondaryTextColor':'#fff', 'tertiaryColor':'#ec4899', 'tertiaryTextColor':'#fff', 'background':'#fdf2f8', 'mainBkg':'#be185d', 'secondBkg':'#db2777', 'tertiaryBkg':'#ec4899', 'fontFamily':'system-ui, sans-serif'}}}%%
flowchart TB
    subgraph AppController["AppController コンポーネント"]
        Flash["Flash"]
        Authentication["Authentication"]
    end

    subgraph Application["Application プラグイン"]
        AuthPlugin["cakephp/authentication"]
    end

    subgraph AuthPluginDetail["Authentication 内訳"]
        Session["Session Authenticator"]
        Form["Form Authenticator"]
        Password["Password Identifier"]
    end

    AppController --> Flash
    AppController --> Authentication
    Application --> AuthPlugin
    AuthPlugin --> Session
    AuthPlugin --> Form
    Form --> Password
```

---

## 9. フロントエンド構成

```mermaid
%%{init: {'theme':'base', 'themeVariables': { 'primaryColor':'#0284c7', 'primaryTextColor':'#fff', 'primaryBorderColor':'#0369a1', 'lineColor':'#38bdf8', 'secondaryColor':'#0ea5e9', 'secondaryTextColor':'#fff', 'tertiaryColor':'#22d3ee', 'tertiaryTextColor':'#fff', 'background':'#f0f9ff', 'mainBkg':'#0284c7', 'secondBkg':'#0ea5e9', 'tertiaryBkg':'#22d3ee', 'fontFamily':'system-ui, sans-serif'}}}%%
flowchart LR
    subgraph Build["⚡ ビルド"]
        Input["input.css"]
        Tailwind["Tailwind v4"]
        Output["tailwind.css"]
    end

    subgraph Templates["📄 テンプレート"]
        Layout["layout"]
        Todos["Todos"]
        Users["Users"]
    end

    Input --> Tailwind
    Tailwind --> Output
    Layout --> Output
    Layout --> Todos
    Layout --> Users
```

---

## 概要まとめ

| 項目 | 内容 |
|------|------|
| フレームワーク | CakePHP 5.x |
| 認証 | cakephp/authentication プラグイン |
| データベース | MySQL |
| フロントエンド | Tailwind CSS v4 |
| 主なテーブル | users, todos |

### 認証不要のアクション
- `UsersController::login` - ログイン
- `UsersController::add` - ユーザー登録
- `UsersController::dbTest` - DB接続テスト

### 認証必須のアクション
- `TodosController` の全アクション（index, view, add, edit, delete）
- `UsersController::logout` - ログアウト
