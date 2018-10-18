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
namespace Passbolt\WebInstaller\Utility;

use App\Model\Entity\Role;
use App\Utility\Healthchecks;
use Cake\Core\Exception\Exception;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;

class DatabaseConnection {

    /**
     * Build a database configuration
     * @param array $data form data
     * @return array
     */
    public static function buildConfig($data)
    {
        return [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Mysql',
            'persistent' => false,
            'host' => $data['host'],
            'port' => $data['port'],
            'username' => $data['username'],
            'password' => $data['password'],
            'database' => $data['database'],
            'encoding' => 'utf8',
            'timezone' => 'UTC',
        ];
    }

    /**
     * Test database connection.
     * @param string $name The connection name
     * @throws Exception when a connection cannot be established
     * @return void
     */
    public static function testConnection($name)
    {
        $connection = ConnectionManager::get($name);
        try {
            $connection->execute('SHOW TABLES')->fetchAll('assoc');
        } catch (\PDOException $e) {
            throw new Exception(__('A connection could not be established with the credentials provided. Please verify the settings.'));
        }
    }

    /**
     * Check that the passbolt database has at least one admin user.
     * @param string $name The connection name
     * @throws Exception when the database schema is not the right one
     * @return int number of admin users
     */
    public static function checkDbHasAdmin($name)
    {
        $connection = ConnectionManager::get($name);

        // Check if database is populated with tables.
        $tables = $connection->execute('SHOW TABLES')->fetchAll();
        $tables = Hash::extract($tables, '{n}.0');

        if (count($tables) == 0) {
            return 0;
        }

        // Database already exist, check whether the schema is valid, and how many admins are there.
        $expected = Healthchecks::getSchemaTables(1);
        foreach ($expected as $expectedTableName) {
            if (!in_array($expectedTableName, $tables)) {
                throw new Exception(__('The database schema does not match the one expected'));
            }
        }

        $roles = TableRegistry::get('Roles');
        $roles->setConnection($connection);

        $users = TableRegistry::get('Users');
        $users->setConnection($connection);

        $roleId = $roles->getIdByName(Role::ADMIN);
        $nbAdmins = $users->find()
            ->where(['role_id' => $roleId])
            ->count();

        return $nbAdmins;
    }
}
