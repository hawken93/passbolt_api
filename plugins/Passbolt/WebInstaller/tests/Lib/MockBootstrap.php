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
namespace Passbolt\WebInstaller\Test\Lib;

use Cake\Core\Plugin;

abstract class MockBootstrap
{

    public static function mockPassboltIsNotconfigured()
    {
        // Mock the bootstrap behavior
        // When passbolt is not configured, the WebInstaller plugin should be loaded
        define('PASSBOLT_IS_CONFIGURED', false);
        Plugin::load('Passbolt/WebInstaller', ['bootstrap' => true, 'routes' => true]);
    }

    public static function mockPassboltIsconfigured()
    {
        // Mock the bootstrap behavior
        // When passbolt is configured, the WebInstaller plugin should not be loaded
        define('PASSBOLT_IS_CONFIGURED', true);
    }
}
