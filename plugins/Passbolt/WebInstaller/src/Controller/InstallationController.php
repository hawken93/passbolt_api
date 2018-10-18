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
namespace Passbolt\WebInstaller\Controller;

use App\Error\Exception\CustomValidationException;
use App\Model\Entity\AuthenticationToken;
use App\Model\Entity\Role;
use App\Model\Entity\User;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\Datasource\ConnectionManager;
use Migrations\Migrations;
use Passbolt\WebInstaller\Utility\DatabaseConnection;

class InstallationController extends WebInstallerController
{

    /**
     * Initialize.
     * @return void
     */
    public function initialize()
    {
        parent::initialize();
        $this->stepInfo['previous'] = 'install/options';
        $this->stepInfo['template'] = 'Pages/email';
        $this->stepInfo['install'] = 'install/installation/do_install';
    }

    /**
     * Index.
     * @return void
     */
    public function index()
    {
        $this->set('redirectUrl', $this->stepInfo['redirectUrl']);
        $user = $this->_getSavedConfiguration(AccountCreationController::MY_CONFIG_KEY);
        $this->set('firstUserCreated', !empty($user));
        $this->render('Pages/installation');
    }

    /**
     * Install passbolt :
     * - write config/passbolt.php
     * - create the database tables
     * - write config/license
     * - create first user
     * - create first user registration token
     *
     * @return void
     */
    public function install()
    {
        $this->_writeConfigurationFile();
        $this->_installDb();
        $this->_writeLicenseFile();

        // The model should be loaded after the db is installed and the datasource config is loaded.
        $this->loadModel('Users');
        $user = $this->_createFirstUser();
        $token = $this->_createFirstUserRegistrationToken($user);

        $this->viewBuilder()->setLayout('ajax');
        $this->set('data', ['user' => $user, 'token' => $token]);
        return $this->render('Pages/installation_result');
    }

    /**
     * Write passbolt configuration file.
     * @return void
     */
    protected function _writeConfigurationFile()
    {
        $session = $this->request->getSession();
        $config = $session->read('Passbolt.Config');

        // Sanitize output before writing the file.
        foreach ($config as $key => $itemConfig) {
            if (is_array($itemConfig)) {
                $config[$key] = $this->_sanitizeEntries($itemConfig);
            } elseif (is_string($itemConfig)) {
                $config[$key] = $this->_sanitizeEntry($itemConfig);
            }
        }

        $this->set(['config' => $config]);
        $configView = $this->createView();
        $contents = $configView->render('/Config/passbolt', 'ajax');
        $contents = "<?php\n$contents";
        file_put_contents(CONFIG . 'passbolt.php', $contents);
        Configure::load('passbolt', 'default', true);
    }

    /**
     * Write the license file.
     * @return void
     */
    protected function _writeLicenseFile()
    {
        if (!Configure::read('passbolt.plugins.license')) {
            return;
        }
        $session = $this->request->getSession();
        $content = $session->read('Passbolt.License');
        file_put_contents(CONFIG . 'license', $content);
    }

    /**
     * Sanitize all entries of a configuration array.
     * Sanitize = we escape the characters ' and \
     * Works on a single dimension array only.
     * @param array $entries list of entries
     * @return mixed
     */
    protected function _sanitizeEntries($entries)
    {
        foreach ($entries as $key => $entry) {
            if (is_string($entry)) {
                $entries[$key] = $this->_sanitizeEntry($entry);
            }
        }

        return $entries;
    }

    /**
     * Sanitize an entry before writing it in a file.
     * @param array $entry list of entries
     * @return mixed
     */
    protected function _sanitizeEntry($entry)
    {
        $entry = addslashes($entry);

        return $entry;
    }

    /**
     * Install database.
     * @throws Exception The database cannot be installed
     * @return mixed
     */
    protected function _installDb()
    {
        unlink(CONFIG . 'passbolt.php'); // @todo remove debug
        ConnectionManager::drop('default');
        $dbConfig = DatabaseConnection::buildConfig(Configure::read('Datasources.default'));
        ConnectionManager::setConfig('default', $dbConfig);
        $migrations = new Migrations();
        $migrated = $migrations->migrate();
        if (!$migrated) {
            throw new Exception('The database cannot be installed');
        }
    }

    /**
     * Create the first user.
     * @throws CustomValidationException There was a problem creating the first user
     * @return User
     */
    protected function _createFirstUser()
    {
        $userData = $this->_getSavedConfiguration(AccountCreationController::MY_CONFIG_KEY);
        if (empty($userData)) {
            return;
        }

        $userData['deleted'] = false;
        $userData['role_id'] = $this->Users->Roles->getIdByName(Role::ADMIN);
        $user = $this->Users->buildEntity($userData);
        $this->Users->save($user, ['checkRules' => true, 'atomic' => false]);
        $errors = $user->getErrors();
        if (!empty($errors)) {
            throw new CustomValidationException('There was a problem creating the first user', $errors, $this->Users);
        }

        return $user;
    }

    /**
     * Create the registration token for the first user.
     * @param User $user The user to create the registration token for
     * @throws CustomValidationException There was a problem creating the registration token
     * @return AuthenticationToken
     */
    protected function _createFirstUserRegistrationToken(User $user)
    {
        $token = $this->Users->AuthenticationTokens->generate($user->id, AuthenticationToken::TYPE_REGISTER);
        $errors = $token->getErrors();
        if (!empty($errors)) {
            throw new CustomValidationException('There was a problem creating the registration token', $errors, $this->Users->AuthenticationTokens);
        }

        return $token;
    }

}
