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

use App\Model\Entity\AuthenticationToken;
use App\Model\Entity\Role;
use Cake\Network\Exception\ForbiddenException;

class AccountCreationController extends WebInstallerController
{

    const MY_CONFIG_KEY = 'first_user';

    /**
     * Initialize.
     * @return void
     */
    public function initialize()
    {
        parent::initialize();
        $this->stepInfo['next'] = 'install/installation';
        $this->stepInfo['template'] = 'Pages/account_creation';
    }

    /**
     * Index
     * @return mixed
     */
    public function index()
    {
        $data = $this->request->getData();
        if (!empty($data)) {
            $this->_saveConfiguration(SELF::MY_CONFIG_KEY, $data);
            return $this->_success();
        }

        $this->render('Pages/account_creation');
    }


}
