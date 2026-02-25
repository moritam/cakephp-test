<?php
/**
 * Vue SPA 用レイアウト（Vite ビルド成果物を読み込む）
 * @var \App\View\AppView $this
 */
$cakeDescription = 'Todo App';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($cakeDescription) ?></title>
    <?= $this->Html->meta('icon') ?>
    <?= $this->Html->css('tailwind.css') ?>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <div id="app"></div>
    <script type="module" src="/app/assets/index.js"></script>
</body>
</html>
