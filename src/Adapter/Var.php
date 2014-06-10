<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @package Aura.Autoload
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth;

/**
 *
 * Authenticate against username-password arrays.
 *
 * @package Aura.Auth
 *
 */
class Auth_Storage_Adapter_Var extends Solar_Auth_Storage_Adapter
{
    /**
     *
     * Default configuration values.
     *
     * @config array data The credential data.
     *
     * @var array
     *
     */
    protected $_Solar_Auth_Storage_Adapter_Var = array(
        'data' => array(),
    );

    /**
     *
     * The credential data.
     *
     * @var array
     *
     */
    protected $_data = array();

    /**
     *
     * Post-construction tasks.
     *
     * @return null
     *
     */
    protected function _postConstruct()
    {
        $this->_data = (array) $this->_config['data'];
    }

    /**
     *
     * Verifies set of credentials.
     *
     * @param array $credentials A list of credentials to verify
     *
     * @return mixed An array of verified user information, or boolean false
     * if verification failed.
     *
     */
    public function validateCredentials($credentials)
    {
        if (empty($credentials['username'])) {
            return false;
        }

        if (empty($credentials['password'])) {
            return false;
        }
        $username = $credentials['username'];
        $password = $credentials['password'];

        // is there a username key in the data array?
        if (! array_key_exists($username, $this->_data)) {
            return false;
        }

        // does the plain-text password match?
        if ($this->_data[$username] === $password) {
            // return the user data
            $user = array('username' => $username);
            return $user;
        } else {
            return false;
        }
    }
}
