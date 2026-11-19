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
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/app/assets/index.css">
</head>
<body>
    <div id="app"></div>
    <script type="module" src="/app/assets/index.js"></script>
</body>
</html>
