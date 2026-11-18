---
description: Docker で本番デプロイする。テスト → フロントエンドビルド → イメージビルド → コンテナ再起動 の順に実行する。
---

# deploy

本番サーバーへのデプロイ手順。`compose.yaml` を使って Docker コンテナをビルド・起動する。
マイグレーションは entrypoint.sh がコンテナ起動時に自動実行するため、手動実行不要。

## 前提確認

デプロイ前に以下を確認する。ユーザーが確認済みと言っていなければ、このステップを実行してから先に進む。

1. **ブランチの確認**: `git status --short` を実行し、未コミットの変更がないことを確認する。あれば止まってユーザーに確認する。
2. **テスト**: `composer test` を実行し、全テストが通ることを確認する。失敗したらデプロイを中止する。
3. **環境変数**: `.env` ファイルが存在するか確認する (`ls -la .env`)。なければ `.env.production.example` をコピーするようユーザーに伝えて止まる。

```bash
cp .env.production.example .env
# .env を編集: SECURITY_SALT, APP_FULL_BASE_URL を必ず設定する
```

## デプロイ手順

確認が取れたら、以下を順番に実行する。

### 1. フロントエンドビルド

```bash
cd frontend && npm run build
```

ビルドエラーがあれば止まる。`webroot/app/` に成果物が出力される。

### 2. イメージビルド & コンテナ再起動

```bash
docker compose up -d --build
```

- `--build` でイメージを再ビルドしてから起動する
- entrypoint.sh が自動で `bin/cake migrations migrate` と `bin/cake cache clear_all` を実行する

### 3. 起動確認

```bash
docker compose ps
docker compose logs --tail=30
```

- `app` コンテナの `Status` が `Up` であることを確認する
- ログに `ERROR:` や `CRIT` が出ていないことを確認する

### 4. ヘルスチェック

```bash
curl -s -o /dev/null -w "%{http_code}" http://localhost:${APP_PORT:-8080}/
```

`302` (→ /app にリダイレクト) が返れば正常。

## ロールバック

問題が起きた場合は前のイメージに戻す:

```bash
# 1つ前のイメージタグを確認
docker images todo-app

# 前のイメージで再起動 (タグを指定)
docker compose down
docker tag todo-app:<前のタグ> todo-app:latest
docker compose up -d
```

## 注意事項

- `SECURITY_SALT` と `APP_FULL_BASE_URL` は必須。未設定だとコンテナが起動しない
- `REGISTRATION_ENABLED` はデフォルト `false`。新規ユーザーは `bin/cake user create` で作成する
- DB は `/data` ボリュームに永続化される。`docker compose down -v` は**データが消えるため絶対に使わない**
