<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateTodos extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('todos');
        $table->addColumn('title', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ])
        ->addColumn('completed', 'boolean', [
            'default' => false,
            'null' => true,
        ])
        ->addColumn('created', 'datetime', [
            'default' => null,
            'null' => true,
        ])
        ->addColumn('modified', 'datetime', [
            'default' => null,
            'null' => true,
        ])
        ->create();
    }
}
