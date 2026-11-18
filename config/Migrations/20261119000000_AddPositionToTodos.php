<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddPositionToTodos extends BaseMigration
{
    public function up(): void
    {
        $table = $this->table('todos');
        $table->addColumn('position', 'integer', [
            'default' => 0,
            'null' => false,
        ]);
        $table->update();

        // 既存レコードに初期 position を設定 (id 順)
        $this->execute('UPDATE todos SET position = id');
    }

    public function down(): void
    {
        $table = $this->table('todos');
        $table->removeColumn('position');
        $table->update();
    }
}
