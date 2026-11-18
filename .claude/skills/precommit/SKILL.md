---
name: precommit
description: PHP (src/, config/, tests/) やフロントエンド (frontend/src/) を変更した後、作業完了の報告やコミットの前に使う。コードスタイル・PHPUnit・フロントエンドビルドを順に実行し、結果をまとめて報告する。
---

# precommit

変更をコミットできる状態か確認する。下の手順を順番に実行し、途中で失敗しても原因が直せるものは直して続ける。

## 1. 変更範囲を把握する

`git status --short` を実行し、PHP 側・フロントエンド側のどちらに変更があるかを確認する。
`tmp/` 配下 (キャッシュ・SQLite) の変更は確認の対象外。

## 2. PHP コードスタイル

PHP ファイルに変更がある場合だけ実行する。

1. `composer cs-check`
2. 失敗したら `composer cs-fix` で自動修正し、もう一度 `composer cs-check`
3. 自動修正できない違反が残ったら、該当箇所を手で直す

## 3. PHPUnit

PHP ファイルに変更がある場合だけ実行する。

1. `composer test`
2. マイグレーションを追加した直後でブートストラップが失敗した場合は、`tmp/tests.sqlite` を削除して再実行する (cakephp/migrations 5.0.1 の既知の問題)
3. テストが失敗したら、テストを書き換えて通すのではなく、まず実装側の誤りを疑う。どちらを直すべきか判断がつかなければユーザーに確認する
4. 終了コードが 0 以外でも、全テストが通っていることがある (PHPUnit の警告・Incomplete でも 1 になる)。その場合は ✅ ではなく ⚠️ とし、警告と Incomplete の内容を報告する。内容は `vendor/bin/phpunit --display-warnings --display-incomplete --colors=always` で確認できる
   - 既知の環境要因: `Failed to set "apc.enable_cli=1"` は、ローカルに APCu 拡張がないために出る警告 (phpunit.xml.dist の ini 設定)。今回の変更が原因ではない

## 4. フロントエンドビルド

`frontend/` に変更がある場合だけ実行する。

1. ビルド前に `git status --short webroot/app` を実行し、結果を控えておく
2. `cd frontend && npm run build`
3. ビルド成果物は `webroot/app/` に出力され、**Git で管理されている**。ビルド後にもう一度 `git status --short webroot/app` を実行し、ビルド前と比べる
   - 前後で同じ: 成果物はすでに最新だった (ビルド済みのものが作業ツリーにある)
   - 前後で違う: 今回のビルドで成果物が変わった。`frontend/src` の変更と一緒にコミットが必要
   - ファイル名のハッシュ (`TodoListView-xxxx.js`) が変わると、古いファイルの削除 (D) と新しいファイルの追加 (??) が出る。両方をコミットに含める

## 5. 報告

次の形式で報告する。実行しなかった項目は「スキップ」と理由を書く。

| 項目 | 結果 | 備考 |
|---|---|---|
| cs-check | ✅ / ❌ / スキップ | 自動修正したファイルなど |
| PHPUnit | ✅ / ⚠️ / ❌ / スキップ | テスト数、失敗したテスト名、警告・Incomplete の内容 |
| frontend build | ✅ / ❌ / スキップ | webroot/app の差分が今回のビルドで変わったか、もともと最新だったか |

失敗が残っている場合は、エラー出力の要点を貼り、「コミットできる状態ではない」とはっきり書く。
