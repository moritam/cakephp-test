<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * CakePHP の BelongsToMany 規約: アルファベット順 (tags < todos) → tags_todos
 */
class CreateTagsTodos extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('tags_todos', [
            'id' => false,
            'primary_key' => ['tag_id', 'todo_id'],
        ]);
        $table
            ->addColumn('tag_id', 'integer', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('todo_id', 'integer', [
                'default' => null,
                'null' => false,
            ])
            ->addForeignKey('tag_id', 'tags', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ])
            ->addForeignKey('todo_id', 'todos', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ])
            ->create();
    }
}
