#!/bin/bash
# PostToolUse (Edit|Write) hook: 編集した PHP ファイルだけを phpcbf で整形する。
# phpcs.xml の対象 (src/, tests/) にあるファイルだけを扱う。
# 整形の失敗は作業を止めない (常に終了コード 0)。

f=$(jq -r '.tool_input.file_path // empty')
[ -n "$f" ] || exit 0

cd "$CLAUDE_PROJECT_DIR" || exit 0
rel=${f#"$CLAUDE_PROJECT_DIR"/}

case "$rel" in
  src/*.php | tests/*.php) ;;
  *) exit 0 ;;
esac

[ -f "$rel" ] || exit 0
# phpcbf の終了コード: 0=変更なし, 1=整形した, 2 以上=直せない違反が残った
vendor/bin/phpcbf -q "$rel" >/dev/null 2>&1
exit 0
