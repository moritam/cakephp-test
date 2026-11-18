<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Command\UserCreateCommand Test Case
 */
class UserCreateCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.Users',
    ];

    /**
     * ユーザーを作成でき、パスワードはハッシュ化して保存される
     *
     * @return void
     */
    public function testCreate(): void
    {
        $this->exec('user create me@example.com', ['password123', 'password123']);

        $this->assertExitSuccess();
        $this->assertOutputContains('ユーザーを作成しました');
        $user = $this->getTableLocator()->get('Users')->findByEmail('me@example.com')->firstOrFail();
        $this->assertNotSame('password123', $user->password);
        $this->assertTrue(password_verify('password123', $user->password));
    }

    /**
     * 確認用のパスワードが違うと作成しない
     *
     * @return void
     */
    public function testPasswordMismatch(): void
    {
        $this->exec('user create me@example.com', ['password123', 'password999']);

        $this->assertExitError();
        $this->assertErrorContains('パスワードが一致しません');
        $this->assertFalse($this->getTableLocator()->get('Users')->exists(['email' => 'me@example.com']));
    }

    /**
     * バリデーション (短いパスワード、既存のメールアドレス) に従う
     *
     * @return void
     */
    public function testValidation(): void
    {
        $this->exec('user create alice@example.com', ['short', 'short']);

        $this->assertExitError();
        $this->assertErrorContains('email:');
        $this->assertErrorContains('password:');
    }
}
