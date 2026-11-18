#!/bin/sh
# コンテナ起動時の準備: 設定の確認 → DB のマイグレーション → キャッシュの削除 → Apache 起動
set -e

if [ -z "${SECURITY_SALT:-}" ] || [ "${SECURITY_SALT}" = "__SALT__" ]; then
    echo "ERROR: 環境変数 SECURITY_SALT を設定してください (例: openssl rand -hex 32)" >&2
    exit 1
fi
if [ "${DEBUG:-false}" != "true" ] && [ -z "${APP_FULL_BASE_URL:-}" ]; then
    echo "ERROR: 環境変数 APP_FULL_BASE_URL を設定してください (例: https://todo.example.ts.net)" >&2
    exit 1
fi

mkdir -p /data/sessions "${BACKUP_DIR:-/data/backups}"
chown -R www-data:www-data /data
chmod 700 /data/sessions "${BACKUP_DIR:-/data/backups}"

cd /var/www/html
# DB ファイルを www-data の所有にするため、www-data で実行する
su www-data -s /bin/sh -c "bin/cake migrations migrate --no-lock -q"
su www-data -s /bin/sh -c "bin/cake cache clear_all -q"

exec "$@"
