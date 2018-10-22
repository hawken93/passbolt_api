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
 * @since         2.5.0
 */
namespace Passbolt\WebInstaller\Utility;

use App\Error\Exception\CustomValidationException;
use App\Model\Entity\AuthenticationToken;
use App\Model\Entity\Role;
use App\Utility\Gpg as AppGpg;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Migrations\Migrations;

class WebInstaller
{
    protected $session = null;
    protected $settings = [];
    public $createdUser = null;
    public $createdUserToken = null;

    public function __construct($session = null) {
        $this->session = $session;
        if (!is_null($session)) {
            $sessionSettings = $session->read('webinstaller');
            if (!empty($sessionSettings)) {
                $this->settings = $sessionSettings;
            }
        }
    }

    public function isInitialized()
    {
        return $this->getSettings('initialized');
    }

    public function getSettings($key) {
        return Hash::get($this->settings, $key);
    }

    public function setSettings($key, $value) {
        $this->settings[$key] = $value;
    }

    public function saveSettings() {
        $this->session->write('webinstaller', $this->settings);
    }

    public function setSettingsAndSave($key, $value)
    {
        $this->setSettings($key, $value);
        $this->saveSettings();
    }

    public function install()
    {
        $this->initDatabaseConnection();
        $this->generateGpgKey();
        $this->importGpgKey();
        $this->writePassboltConfigFile();
        $this->installDatabase();
        $this->writeLicenseFile();
        $this->createFirstUser();
        $this->saveSettings();
    }

    /**
     * Initialize the database connection.
     */
    public function initDatabaseConnection()
    {
        $databaseSettings = $this->getSettings('database');
        DatabaseConfiguration::setDefaultConfig($databaseSettings);
    }

    /**
     * Generate the gpg key
     */
    public function generateGpgKey()
    {
        $gpgSettings = $this->getSettings('gpg');
        if (!isset($gpgSettings['name'])) {
            return;
        }

        $fingerprint = Gpg::generateKey($gpgSettings);
        Gpg::exportPublicArmoredKey($fingerprint, Configure::read('passbolt.gpg.serverKey.public'));
        Gpg::exportPrivateArmoredKey($fingerprint, Configure::read('passbolt.gpg.serverKey.private'));
        $gpgSettings += [
            'fingerprint' => $fingerprint,
            'public' => Configure::read('passbolt.gpg.serverKey.public'),
            'private' => Configure::read('passbolt.gpg.serverKey.private')
        ];
        $this->setSettings('gpg', $gpgSettings);
    }

    /**
     * Import the server gpg key into the gpg keyring.
     * Generate it if information provided.
     */
    public function importGpgKey()
    {
        $gpgSettings = $this->getSettings('gpg');
        if (!isset($gpgSettings['armored_key'])) {
            return;
        }

        $gpg = new AppGpg();
        $fingerprint = $gpg->importKeyIntoKeyring($gpgSettings['armored_key']);
        Gpg::exportPublicArmoredKey($fingerprint, Configure::read('passbolt.gpg.serverKey.public'));
        Gpg::exportPrivateArmoredKey($fingerprint, Configure::read('passbolt.gpg.serverKey.private'));
        $gpgSettings += [
            'fingerprint' => $fingerprint,
            'public' => Configure::read('passbolt.gpg.serverKey.public'),
            'private' => Configure::read('passbolt.gpg.serverKey.private')
        ];
        $this->setSettings('gpg', $gpgSettings);
    }

    /**
     * Write passbolt configuration file.
     * @return void
     */
    public function writePassboltConfigFile()
    {
        $passboltConfig = new PassboltConfiguration();
        $contents = $passboltConfig->render($this->settings);
        file_put_contents(CONFIG . 'passbolt.php', $contents);
    }

    /**
     * Write the license file.
     * @return void
     */
    public function writeLicenseFile()
    {
        if (!Configure::read('passbolt.plugins.license')) {
            return;
        }
        $license = $this->webInstaller->getSettings('license');
        file_put_contents(CONFIG . 'license', $license);
    }

    /**
     * Install database.
     * @throws Exception The database cannot be installed
     * @return mixed
     */
    public function installDatabase()
    {
        $migrations = new Migrations();
        $migrated = $migrations->migrate(['connection' => DatabaseConfiguration::getDefaultConfigName()]);
        if (!$migrated) {
            throw new Exception('The database cannot be installed');
        }
    }

    /**
     * Create the first user.
     * @throws CustomValidationException There was a problem creating the first user
     * @return void
     */
    public function createFirstUser()
    {
        $userData = $this->getSettings('first_user');
        if (empty($userData)) {
            return;
        }

        $Users = TableRegistry::get('Users');
        $userData['deleted'] = false;
        $userData['role_id'] = $Users->Roles->getIdByName(Role::ADMIN);

        $user = $Users->buildEntity($userData);
        $Users->save($user, ['checkRules' => true, 'atomic' => false]);
        $errors = $user->getErrors();
        if (!empty($errors)) {
            throw new CustomValidationException('There was a problem creating the first user', $errors, $Users);
        }

        $token = $Users->AuthenticationTokens->generate($user->id, AuthenticationToken::TYPE_REGISTER);
        $errors = $token->getErrors();
        if (!empty($errors)) {
            throw new CustomValidationException('There was a problem creating the registration token', $errors, $Users->AuthenticationTokens);
        }

        $this->setSettings('user', [
            'user_id' => $user->id,
            'token' => $token->token
        ]);
    }
}