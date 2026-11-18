<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersFixture
 */
class UsersFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'email' => 'alice@example.com',
                'password' => 'not-a-real-hash',
                'created' => '2026-02-23 23:00:00',
                'modified' => '2026-02-23 23:00:00',
            ],
            [
                'id' => 2,
                'email' => 'bob@example.com',
                'password' => 'not-a-real-hash',
                'created' => '2026-02-23 23:00:00',
                'modified' => '2026-02-23 23:00:00',
            ],
        ];
        parent::init();
    }
}
