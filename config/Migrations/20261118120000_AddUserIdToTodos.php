<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * todos にオーナー (user_id) を追加し、ユーザーごとにデータを分離する
 */
class AddUserIdToTodos extends BaseMigration
{
    /**
     * Migrate up.
     *
     * @return void
     */
    public function up(): void
    {
        // 既存行があるため、まず NULL 許可で追加する
        $this->table('todos')
            ->addColumn('user_id', 'integer', [
                'default' => null,
                'null' => true,
                'after' => 'id',
            ])
            ->update();

        // 既存の Todo は最初に登録されたユーザーのものにする (ユーザーがいなければ削除)
        $this->execute(
            'UPDATE todos SET user_id = (SELECT MIN(id) FROM users) WHERE user_id IS NULL',
        );
        $this->execute('DELETE FROM todos WHERE user_id IS NULL');

        $this->table('todos')
            ->changeColumn('user_id', 'integer', [
                'default' => null,
                'null' => false,
            ])
            ->addIndex(['user_id'])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ])
            ->update();
    }

    /**
     * Migrate down.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('todos')
            ->dropForeignKey('user_id')
            ->removeIndex(['user_id'])
            ->removeColumn('user_id')
            ->update();
    }
}
