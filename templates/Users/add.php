<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */
?>
<div class="users form">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 max-w-md mx-auto">
        <h2 class="text-2xl font-bold text-slate-800 mb-6">ユーザー登録</h2>
        <?= $this->Form->create($user, ['class' => 'space-y-6']) ?>
        <fieldset class="space-y-4">
            <legend class="sr-only"><?= __('ユーザー情報を入力してください') ?></legend>
            <?= $this->Form->control('email', [
                'type' => 'email',
                'required' => true,
                'label' => ['text' => 'メールアドレス', 'class' => 'block text-sm font-medium text-slate-700 mb-1'],
                'class' => 'form-input',
            ]) ?>
            <?= $this->Form->control('password', [
                'required' => true,
                'label' => ['text' => 'パスワード（8文字以上）', 'class' => 'block text-sm font-medium text-slate-700 mb-1'],
                'class' => 'form-input',
            ]) ?>
        </fieldset>
        <div class="mt-6">
            <?= $this->Form->button(__('登録'), ['class' => 'btn-primary w-full']) ?>
        </div>
        <?= $this->Form->end() ?>

        <p class="mt-4 text-center text-sm text-slate-600">
            <?= $this->Html->link('ログイン', ['action' => 'login'], ['class' => 'text-blue-600 hover:text-blue-800 font-medium']) ?>
        </p>
    </div>
</div>
