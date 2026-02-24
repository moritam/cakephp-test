<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TodosFixture
 */
class TodosFixture extends TestFixture
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
                'title' => 'Lorem ipsum dolor sit amet',
                'completed' => 1,
                'created' => '2026-02-23 23:14:22',
                'modified' => '2026-02-23 23:14:22',
            ],
        ];
        parent::init();
    }
}
