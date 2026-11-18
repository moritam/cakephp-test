<?php
declare(strict_types=1);

namespace App\Test\TestCase;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Cookie (セッション・CSRF) のセキュリティ設定のテスト
 */
class CookieSettingsTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * セッションは独自の Cookie 名・HttpOnly・SameSite=Lax・strict mode で動く
     *
     * @return void
     */
    public function testSessionConfig(): void
    {
        $this->assertSame('TODOAPP_SESSION', Configure::read('Session.cookie'));
        $this->assertGreaterThan(0, Configure::read('Session.timeout'));
        $ini = Configure::read('Session.ini');
        $this->assertTrue($ini['session.cookie_httponly']);
        $this->assertSame('Lax', $ini['session.cookie_samesite']);
        $this->assertTrue($ini['session.use_strict_mode']);
        $this->assertTrue($ini['session.use_only_cookies']);
    }

    /**
     * Secure 属性はセッション Cookie と CSRF Cookie で同じ設定 (App.secureCookies) を使う
     *
     * @return void
     */
    public function testSessionSecureFollowsAppSetting(): void
    {
        $ini = Configure::read('Session.ini');
        $this->assertArrayHasKey('session.cookie_secure', $ini);
        $this->assertArrayNotHasKey('session', $ini, 'ドット記法で入れ子になっていないこと');
        $this->assertSame(Configure::read('App.secureCookies'), $ini['session.cookie_secure']);
    }

    /**
     * 開発 (Secure なし) の CSRF Cookie: HttpOnly・SameSite=Lax
     *
     * @return void
     */
    public function testCsrfCookieWithoutSecure(): void
    {
        Configure::write('App.secureCookies', false);
        $this->get('/users/csrf-token');

        $cookie = $this->_response->getCookie('csrfToken');
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie['httponly']);
        $this->assertSame('Lax', $cookie['samesite']);
        $this->assertFalse($cookie['secure']);
    }

    /**
     * 本番 (Secure あり) の CSRF Cookie には Secure が付く
     *
     * @return void
     */
    public function testCsrfCookieWithSecure(): void
    {
        Configure::write('App.secureCookies', true);
        $this->get('/users/csrf-token');

        $cookie = $this->_response->getCookie('csrfToken');
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie['secure']);
        $this->assertTrue($cookie['httponly']);
        $this->assertSame('Lax', $cookie['samesite']);
    }
}
