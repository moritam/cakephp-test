---
name: pr
description: 今の変更をブランチにコミットして push し、GitHub の Pull Request を作る。マージはしない。ユーザーが /pr と打ったときだけ使う。
argument-hint: "[PR の要旨]"
disable-model-invocation: true
allowed-tools: Bash(git status*), Bash(git diff*), Bash(git log*), Bash(git add*), Bash(git commit*), Bash(git switch*), Bash(git push -u origin*), Bash(gh pr create*), Bash(gh pr view*), Bash(gh pr checks*)
---

# pr

変更をブランチに載せて PR を作る。main への取り込みはレビューと CI の後に人が判断するので、この Skill ではマージしない。

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

PR の要旨: $ARGUMENTS

空なら差分から考える。件名は Conventional Commits 形式 (`feat:` / `fix:` / `chore:` ...) の英語。

## 手順

1. **前提確認**
   - 「変更」が空で、ブランチが `main` なら、「PR にする変更がない」と伝えて終わる。
   - ブランチが `main` 以外で変更が空なら、そのブランチのコミットで PR を作る (4 へ)。
2. **コミットする範囲を決める**
   - `git diff` で中身を確認し、この作業に関係するファイルだけを選ぶ。
   - `config/app_local.php`、`config/.env`、`tmp/` 配下、関係のない既存の変更は含めない。迷ったらユーザーに確認する。
   - PHP やフロントエンドを変えているのに、この会話で `precommit` を実行していなければ、先に実行するよう勧める。
3. **ブランチを作ってコミット**
   - `main` にいるなら `git switch -c <type>/<短い説明>` で切る。
   - ファイル名を指定して `git add` する (`-A` や `.` は使わない)。
   - コミットメッセージの最後に、システムが指定する Co-Authored-By 行を付ける。
4. **push して PR を作る**
   - `git push -u origin <branch>`
   - `gh pr create --base main --title "<件名>" --body "<本文>"`。本文は次の形にし、最後にシステムが指定する PR の署名行を付ける。

     ```
     ## 概要
     <何をなぜ変えたか 1〜3 行>

     ## 変更点
     - <ファイル/機能ごとの要点>

     ## 確認方法
     - <実行したテストや手順。未実施ならそう書く>
     ```
5. **報告**
   - PR の URL と番号を伝える。
   - 次の手順を案内する (ここでは実行しない):
     - レビュー: `/code-review --comment <番号>` で PR にインラインコメントを付ける
     - CI: `gh pr checks <番号> --watch`
     - マージ: 問題なければユーザーの指示で `gh pr merge <番号> --rebase --delete-branch` を実行し、`git switch main && git pull --ff-only` で手元を揃える
