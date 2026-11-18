<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PDO;

/**
 * App\Command\DbBackupCommand Test Case
 */
class DbBackupCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.Todos',
    ];

    private string $dir;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = TMP . 'backup_test_' . bin2hex(random_bytes(4));
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        foreach (glob($this->dir . DS . '*') ?: [] as $file) {
            unlink($file);
        }
        if (is_dir($this->dir)) {
            rmdir($this->dir);
        }
        parent::tearDown();
    }

    /**
     * バックアップを作成でき、中身は元の DB と同じ
     *
     * @return void
     */
    public function testBackup(): void
    {
        $this->exec("db backup --connection test --dir {$this->dir}");

        $this->assertExitSuccess();
        $files = glob($this->dir . DS . 'todo_app-*.sqlite');
        $this->assertCount(1, $files);
        $pdo = new PDO('sqlite:' . $files[0]);
        $this->assertSame(2, (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn());
        $this->assertSame(2, (int)$pdo->query('SELECT COUNT(*) FROM todos')->fetchColumn());
        $this->assertSame('600', substr(sprintf('%o', fileperms($files[0])), -3));
    }

    /**
     * --keep を超えた古いバックアップは削除される
     *
     * @return void
     */
    public function testKeepRemovesOldBackups(): void
    {
        mkdir($this->dir);
        foreach (['20200101-000000', '20200102-000000', '20200103-000000'] as $stamp) {
            touch($this->dir . DS . "todo_app-{$stamp}.sqlite");
        }

        $this->exec("db backup --connection test --dir {$this->dir} --keep 2");

        $this->assertExitSuccess();
        $files = array_map('basename', glob($this->dir . DS . 'todo_app-*.sqlite'));
        $this->assertCount(2, $files);
        $this->assertContains('todo_app-20200103-000000.sqlite', $files);
        $this->assertNotContains('todo_app-20200101-000000.sqlite', $files);
    }
}
