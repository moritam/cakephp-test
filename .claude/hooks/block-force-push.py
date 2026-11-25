#!/usr/bin/env python3
"""PreToolUse (Bash) hook: git の force push をブロックする。

stdin で受け取ったツール入力の command を単語に分け、`git push` の引数に
-f / --force / --force-with-lease / --force-if-includes / "+refspec" があれば
終了コード 2 で止める (stderr の内容が Claude に伝わる)。
コミットメッセージ中の "--force" や `npm install --force` などは対象外。
"""
import json
import re
import shlex
import sys

SEPARATORS = {"&&", "||", ";", "|", "&"}
# 値を取る git のグローバルオプション (git -C <path> push のように push の前に来る)
GIT_OPTS_WITH_VALUE = {"-C", "-c", "--git-dir", "--work-tree", "--namespace"}


def is_force_arg(arg: str) -> bool:
    if arg in ("--force", "--force-if-includes") or arg.startswith("--force-with-lease"):
        return True
    if re.fullmatch(r"-[a-zA-Z]*f[a-zA-Z]*", arg):  # -f, -uf などの短縮形
        return True
    return arg.startswith("+") and len(arg) > 1  # +main のような強制 refspec


def segments(tokens):
    seg = []
    for tok in tokens:
        if tok in SEPARATORS:
            if seg:
                yield seg
            seg = []
        else:
            seg.append(tok)
    if seg:
        yield seg


def is_force_push(command: str) -> bool:
    lexer = shlex.shlex(command, posix=True, punctuation_chars=";&|")
    lexer.whitespace_split = True
    tokens = list(lexer)
    for seg in segments(tokens):
        if "git" not in seg:
            continue
        i = seg.index("git") + 1
        while i < len(seg) and seg[i].startswith("-"):  # グローバルオプションを飛ばす
            i += 2 if seg[i] in GIT_OPTS_WITH_VALUE else 1
        if i < len(seg) and seg[i] == "push":
            if any(is_force_arg(a) for a in seg[i + 1:]):
                return True
    return False


def main() -> int:
    data = json.load(sys.stdin)
    command = data.get("tool_input", {}).get("command", "")
    try:
        blocked = is_force_push(command)
    except ValueError:
        # クォートが閉じていないなど解析できない場合は、安全側に倒して大まかに判定
        blocked = bool(re.search(r"\bgit\b.*\bpush\b.*(\s-\w*f|--force|\s\+\S)", command))
    if blocked:
        print("force push はブロックされています。通常の git push を使ってください。", file=sys.stderr)
        return 2
    return 0


if __name__ == "__main__":
    sys.exit(main())
