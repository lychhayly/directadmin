<?php
/**
 * DirectAdmin
 * (c) Omines Internetbureau B.V.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Omines\DirectAdmin\DirectAdmin;

/**
 * AccountManagementTest
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com
 */
class AccountManagementTest extends \PHPUnit_Framework_TestCase
{
    const TEST_EMAIL = 'test@127.0.0.1';

    /**
     * This function is explicitly implemented as setup, not teardown, so in case of failed tests you may investigate
     * the accounts in DirectAdmin to see what's wrong.
     */
    public static function setUpBeforeClass()
    {
        try
        {
            // Ensure all test accounts are gone
            $adminContext = DirectAdmin::connectAdmin(DIRECTADMIN_URL, MASTER_ADMIN_USERNAME, MASTER_ADMIN_PASSWORD);
            $adminContext->deleteAccounts([USER_USERNAME, RESELLER_USERNAME, ADMIN_USERNAME]);
        }
        catch(\Exception $e)
        {
            // Silently fail as this is expected behaviour
        }
    }

    public function testCreateAdmin()
    {
        $adminContext = DirectAdmin::connectAdmin(DIRECTADMIN_URL, MASTER_ADMIN_USERNAME, MASTER_ADMIN_PASSWORD);
        $admin = $adminContext->createAdmin(ADMIN_USERNAME, ADMIN_PASSWORD, self::TEST_EMAIL);
        $this->assertEquals(ADMIN_USERNAME, $admin->getUsername());
        $this->assertEquals(DirectAdmin::ACCOUNT_TYPE_ADMIN, $admin->getType());
    }

    /**
     * @depends testCreateAdmin
     */
    public function testCreateReseller()
    {
        $adminContext = DirectAdmin::connectAdmin(DIRECTADMIN_URL, ADMIN_USERNAME, ADMIN_PASSWORD);
        $reseller = $adminContext->createReseller(RESELLER_USERNAME, RESELLER_PASSWORD,
                        self::TEST_EMAIL, 'reseller.test.example.org');
        $this->assertEquals(RESELLER_USERNAME, $reseller->getUsername());
        $this->assertEquals(DirectAdmin::ACCOUNT_TYPE_RESELLER, $reseller->getType());
    }

    /**
     * @depends testCreateReseller
     */
    public function testCreateUser()
    {
        $resellerContext = DirectAdmin::connectReseller(DIRECTADMIN_URL, RESELLER_USERNAME, RESELLER_PASSWORD);
        $this->assertNotEmpty($ips = $resellerContext->getIPs());
        $user = $resellerContext->createUser(USER_USERNAME, USER_PASSWORD,
                        self::TEST_EMAIL, 'user.test.example.org', $ips[0]);
        $this->assertEquals(USER_USERNAME, $user->getUsername());
        $this->assertEquals(DirectAdmin::ACCOUNT_TYPE_USER, $user->getType());
    }

    public function testDeleteAccounts()
    {
        // Have to separately delete the user as otherwise the order is not determined whether it's containing
        // reseller is removed first
        $adminContext = DirectAdmin::connectAdmin(DIRECTADMIN_URL, MASTER_ADMIN_USERNAME, MASTER_ADMIN_PASSWORD);
        $adminContext->deleteAccount(USER_USERNAME);
        $adminContext->deleteAccounts([RESELLER_USERNAME, ADMIN_USERNAME]);
    }
}
