<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateTags extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('tags');
        $table
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('color', 'string', [
                'default' => '#6b7280',
                'limit' => 7,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->addIndex(['name'], ['unique' => true])
            ->create();
    }
}
