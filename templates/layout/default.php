<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * @var \App\View\AppView $this
 */

$cakeDescription = 'Todo App';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= $cakeDescription ?>:
        <?= $this->fetch('title') ?>
    </title>
    <?= $this->Html->meta('icon') ?>

    <?= $this->Html->css('tailwind.css') ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <nav class="bg-white shadow-sm border-b border-slate-200">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-14 items-center">
                <a href="<?= $this->Url->build('/') ?>" class="text-xl font-semibold text-slate-800 hover:text-slate-600">
                    <?= $cakeDescription ?>
                </a>
                <div class="flex gap-4">
                    <?php if ($this->Identity->isLoggedIn()): ?>
                        <a href="<?= $this->Url->build(['controller' => 'Todos', 'action' => 'index']) ?>" class="text-slate-600 hover:text-slate-900 font-medium">Todo一覧</a>
                        <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'logout']) ?>" class="text-slate-600 hover:text-slate-900 font-medium">ログアウト</a>
                    <?php else: ?>
                        <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login']) ?>" class="text-slate-600 hover:text-slate-900 font-medium">ログイン</a>
                        <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'add']) ?>" class="text-slate-600 hover:text-slate-900 font-medium">ユーザー登録</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
    </main>
    <footer class="border-t border-slate-200 mt-12 py-6 text-center text-slate-500 text-sm">
        &copy; <?= date('Y') ?> <?= $cakeDescription ?>
    </footer>
</body>
</html>
