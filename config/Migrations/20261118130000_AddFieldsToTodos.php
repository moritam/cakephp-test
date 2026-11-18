<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * todos に priority / due_date / description を追加する
 */
class AddFieldsToTodos extends BaseMigration
{
    public function up(): void
    {
        $this->table('todos')
            ->addColumn('priority', 'string', [
                'default' => 'medium',
                'limit' => 10,
                'null' => false,
                'after' => 'completed',
            ])
            ->addColumn('due_date', 'date', [
                'default' => null,
                'null' => true,
                'after' => 'priority',
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'null' => true,
                'after' => 'due_date',
            ])
            ->addIndex(['priority'])
            ->addIndex(['due_date'])
            ->update();
    }

    public function down(): void
    {
        $this->table('todos')
            ->removeIndex(['due_date'])
            ->removeIndex(['priority'])
            ->removeColumn('description')
            ->removeColumn('due_date')
            ->removeColumn('priority')
            ->update();
    }
}
