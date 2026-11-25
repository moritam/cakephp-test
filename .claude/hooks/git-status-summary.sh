#!/bin/bash
# Stop hook: 未コミットの変更があれば、件数とファイル名を systemMessage でユーザーに表示する。
# Stop hook の素の stdout はユーザーに表示されないため、JSON の systemMessage で返す。
# 変更がなければ何も出さない。

cd "$CLAUDE_PROJECT_DIR" || exit 0
status=$(git status --porcelain 2>/dev/null) || exit 0
[ -n "$status" ] || exit 0

count=$(printf '%s\n' "$status" | wc -l | tr -d ' ')
files=$(printf '%s\n' "$status" | head -5 | sed 's/^/  /')
more=""
[ "$count" -gt 5 ] && more=$'\n'"  ...ほか $((count - 5)) 件"

jq -n --arg msg "未コミットの変更: ${count} 件"$'\n'"${files}${more}" '{systemMessage: $msg}'
