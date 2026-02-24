<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Todo $todo
 */
?>
<div class="todos form">
    <div class="mb-6 flex gap-4">
        <?= $this->Form->postLink(
            __('Delete'),
            ['action' => 'delete', $todo->id],
            ['confirm' => __('Are you sure you want to delete # {0}?', $todo->id), 'class' => 'text-red-600 hover:text-red-900']
        ) ?>
        <?= $this->Html->link(__('List Todos'), ['action' => 'index'], ['class' => 'text-slate-600 hover:text-slate-900']) ?>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-xl">
        <h2 class="text-2xl font-bold text-slate-800 mb-6"><?= __('Edit Todo') ?></h2>
        <?= $this->Form->create($todo, ['class' => 'space-y-6']) ?>
        <fieldset class="space-y-4">
            <?php
                echo $this->Form->control('title', [
                    'class' => 'form-input',
                    'label' => ['class' => 'block text-sm font-medium text-slate-700 mb-1'],
                ]);
                echo $this->Form->control('completed', [
                    'type' => 'checkbox',
                    'class' => 'form-checkbox',
                    'label' => ['class' => 'ml-2 text-sm text-slate-700'],
                ]);
            ?>
        </fieldset>
        <div class="flex gap-3 mt-6">
            <?= $this->Form->button(__('Save'), ['class' => 'btn-primary']) ?>
            <?= $this->Html->link(__('Cancel'), ['action' => 'index'], ['class' => 'btn-secondary']) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>
