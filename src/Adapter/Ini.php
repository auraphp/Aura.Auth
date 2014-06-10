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
 * Authenticate against .ini style files.
 *
 * Each group is a user username, with keys for 'password', 'displayname', 'email',
 * and 'uri'.  For example ...
 *
 *     [pmjones]
 *     password = plaintextpass
 *     email = pmjones@solarphp.com
 *     displayname = Paul M. Jones
 *     uri = http://paul-m-jones.com/
 *
 * @package Aura.Auth
 *
 */
class Auth_Storage_Adapter_Ini extends Solar_Auth_Storage_Adapter
{
    /**
     *
     * Default configuration values.
     *
     * @config string file Path to .ini file.
     *
     * @var array
     *
     */
    protected $_Solar_Auth_Storage_Adapter_Ini = array(
        'file' => null,
    );


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

        // force the full, real path to the .ini file
        $file = realpath($this->_config['file']);

        // does the file exist?
        if (! file_exists($file) || ! is_readable($file)) {
            throw $this->_exception('ERR_FILE_NOT_READABLE', array(
                'file' => $file,
            ));
        }

        // parse the file into an array
        $data = parse_ini_file($file, true);

        // get user info for the username
        $user = (! empty($data[$username])) ? $data[$username] : array();

        // there must be an entry for the username,
        // and the plain-text password must match.
        if (! empty($user['password']) && $user['password'] == $password) {
            // insert the username, and get rid of the password
            $user['username'] = $username;
            unset($user['password']);
            return $user;
        } else {
            return false;
        }
    }
}
