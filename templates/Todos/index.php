<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Todo> $todos
 */
?>
<div class="todos index">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800"><?= __('Todos') ?></h2>
        <?= $this->Html->link(__('New TODO'), ['action' => 'add'], ['class' => 'btn-primary']) ?>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase"><?= $this->Paginator->sort('id') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase"><?= $this->Paginator->sort('title') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase"><?= $this->Paginator->sort('completed') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase"><?= $this->Paginator->sort('created') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase"><?= $this->Paginator->sort('modified') ?></th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase"><?= __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    <?php foreach ($todos as $todo): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900"><?= $this->Number->format($todo->id) ?></td>
                        <td class="px-6 py-4 text-sm text-slate-900"><?= h($todo->title) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full <?= $todo->completed ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800' ?>">
                                <?= $todo->completed ? __('Done') : __('Not done') ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?= h($todo->created) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?= h($todo->modified) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <?= $this->Html->link(__('View'), ['action' => 'view', $todo->id], ['class' => 'text-blue-600 hover:text-blue-900']) ?>
                            <?= $this->Html->link(__('Edit'), ['action' => 'edit', $todo->id], ['class' => 'text-blue-600 hover:text-blue-900']) ?>
                            <?= $this->Form->postLink(
                                __('Delete'),
                                ['action' => 'delete', $todo->id],
                                [
                                    'method' => 'delete',
                                    'confirm' => __('Are you sure you want to delete # {0}?', $todo->id),
                                    'class' => 'text-red-600 hover:text-red-900',
                                ]
                            ) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="paginator mt-6 flex items-center justify-between gap-4 flex-wrap">
        <p class="text-sm text-slate-600"><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
        <ul class="flex gap-2 flex-wrap">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
    </div>
</div>
