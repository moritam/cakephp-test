<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Todo $todo
 */
?>
<div class="todos view">
    <div class="flex gap-4 mb-6">
        <?= $this->Html->link(__('Edit Todo'), ['action' => 'edit', $todo->id], ['class' => 'btn-primary']) ?>
        <?= $this->Form->postLink(__('Delete Todo'), ['action' => 'delete', $todo->id], ['confirm' => __('Are you sure you want to delete # {0}?', $todo->id), 'class' => 'btn-danger']) ?>
        <?= $this->Html->link(__('List Todos'), ['action' => 'index'], ['class' => 'btn-secondary']) ?>
        <?= $this->Html->link(__('New Todo'), ['action' => 'add'], ['class' => 'btn-secondary']) ?>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-2xl font-bold text-slate-800 mb-6"><?= h($todo->title) ?></h2>
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="border-b border-slate-200 pb-4">
                <dt class="text-sm font-medium text-slate-500"><?= __('Title') ?></dt>
                <dd class="mt-1 text-sm text-slate-900"><?= h($todo->title) ?></dd>
            </div>
            <div class="border-b border-slate-200 pb-4">
                <dt class="text-sm font-medium text-slate-500"><?= __('Id') ?></dt>
                <dd class="mt-1 text-sm text-slate-900"><?= $this->Number->format($todo->id) ?></dd>
            </div>
            <div class="border-b border-slate-200 pb-4">
                <dt class="text-sm font-medium text-slate-500"><?= __('Created') ?></dt>
                <dd class="mt-1 text-sm text-slate-900"><?= h($todo->created) ?></dd>
            </div>
            <div class="border-b border-slate-200 pb-4">
                <dt class="text-sm font-medium text-slate-500"><?= __('Modified') ?></dt>
                <dd class="mt-1 text-sm text-slate-900"><?= h($todo->modified) ?></dd>
            </div>
            <div class="pb-4">
                <dt class="text-sm font-medium text-slate-500"><?= __('Completed') ?></dt>
                <dd class="mt-1">
                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full <?= $todo->completed ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800' ?>">
                        <?= $todo->completed ? __('Yes') : __('No') ?>
                    </span>
                </dd>
            </div>
        </dl>
    </div>
</div>
