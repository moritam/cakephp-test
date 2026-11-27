---
name: ship
description: 今の変更を短命ブランチでコミットし、main に fast-forward で取り込んで push する。ユーザーが /ship と打ったときだけ使う。
argument-hint: "[コミットメッセージの要旨]"
disable-model-invocation: true
allowed-tools: Bash(git status*), Bash(git diff*), Bash(git log*), Bash(git add*), Bash(git commit*), Bash(git switch*), Bash(git merge --ff-only*), Bash(git branch -d*), Bash(git push origin main)
---

# ship

変更を main に載せて push する。push は外部に公開する操作なので、手順を飛ばさない。

## 現在の状態 (Skill の読み込み時に実行済み)

- ブランチ: !`git branch --show-current`
- 変更:

```
!`git status --short`
```

- 直近のコミット (メッセージの書き方を合わせる):

```
!`git log --oneline -5`
```

## 引数

コミットメッセージの要旨: $ARGUMENTS

空なら、差分から Conventional Commits 形式 (`feat:` / `fix:` / `chore:` / `docs:` ...) の英語の件名を考える。

## 手順

1. **前提確認**
   - 上の「変更」が空なら、何もせず「コミットする変更がない」と伝えて終わる。
   - ブランチが `main` 以外なら、そのブランチのまま 3 に進むか、ユーザーに確認する。
2. **コミットする範囲を決める**
   - `git diff` で中身を確認し、この作業に関係するファイルだけを選ぶ。
   - `config/app_local.php`、`config/.env`、`tmp/` 配下、関係のない既存の変更は含めない。迷うファイルがあれば、ユーザーに確認する。
   - PHP やフロントエンドを変えているのに、この会話で `precommit` を実行していなければ、先に実行するよう勧める。
3. **ブランチを作ってコミット**
   - `git switch -c <type>/<短い説明>` (例: `chore/ship-skill`)
   - ファイル名を指定して `git add` する (`git add -A` や `git add .` は使わない)。
   - コミットメッセージの最後に、システムが指定する Co-Authored-By 行を付ける。
4. **main に取り込んで push**
   - `git switch main` → `git merge --ff-only <branch>` → `git push origin main` → `git branch -d <branch>`
   - ff-merge が失敗したら (main が先に進んでいる)、push せずに止まってユーザーに伝える。
   - **注意:** このコミットで追跡を外したファイル (`git rm --cached`) があるときは、`git switch main` で手元のファイルが消える。先に退避するか、`git push origin HEAD:main` → `git branch -f main HEAD` の順で取り込む。
5. **報告**
   - push した範囲 (`abc123..def456`)、コミットの件名、含めたファイル、作業ツリーに残っている変更を短く伝える。
