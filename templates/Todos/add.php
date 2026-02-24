<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Todo $todo
 */
?>
<div class="todos form">
    <div class="mb-6">
        <?= $this->Html->link(__('List Todos'), ['action' => 'index'], ['class' => 'text-slate-600 hover:text-slate-900']) ?>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-xl">
        <h2 class="text-2xl font-bold text-slate-800 mb-6"><?= __('Add Todo') ?></h2>
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
            <?= $this->Form->button(__('Submit'), ['class' => 'btn-primary']) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>
