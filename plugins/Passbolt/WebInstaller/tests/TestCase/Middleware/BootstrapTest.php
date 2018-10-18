<?php
/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SARL (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SARL (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         2.0.0
 */
namespace Passbolt\WebInstaller\Test\TestCase\Middleware;

use App\Test\Lib\AppIntegrationTestCase;
use Cake\Core\Configure;
use Passbolt\WebInstaller\Test\Lib\MockBootstrap;

class WebInstallerMiddlewareTest extends AppIntegrationTestCase
{
    protected $preserveGlobalState = FALSE;
    protected $runTestInSeparateProcess = TRUE;

    public function testNotConfigured_GoToInstall_Success()
    {
        MockBootstrap::mockPassboltIsNotconfigured();
        $this->get('/install');
        $data = ($this->_getBodyAsString());
        $this->assertResponseOk();
        $this->assertContains('<div id="container" class="page setup install', $data);
    }

    public function testNotConfigured_RedirectAllToInstall_Success()
    {
        MockBootstrap::mockPassboltIsNotconfigured();
        $uris = ['/', 'auth/login', 'resources.json', 'users/recover'];
        foreach ($uris as $uri) {
            $this->get($uri);
            $this->assertResponseCode(302);
            $this->assertRedirectContains('/install');
        }
    }

    public function testAlreadyConfigured_GoToInstall_Error()
    {
        MockBootstrap::mockPassboltIsconfigured();
        $this->get('/install');
        $this->assertResponseCode(404);
    }
}
