<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @package Aura.Auth
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth\Adapter;

/**
 *
 * Authenticate against an LDAP server.
 *
 * @package Aura.Auth
 *
 */
class Auth_Storage_Adapter_Ldap extends Solar_Auth_Storage_Adapter
{
    /**
     *
     * Default configuration values.
     *
     * @config string uri URL to the LDAP server, for example "ldaps://example.com:389".
     *
     * @config string format Sprintf() format string for the LDAP query; %s
     *   represents the username.  Example: "uid=%s,dc=example,dc=com".
     *
     * @config string filter A regular-expression snippet that lists allowed characters
     *   in the username.  This is to help prevent LDAP injections.  Default
     *   expression is '\w' (that is, only word characters are allowed).
     *
     * @var array
     *
     */
    protected $_Solar_Auth_Storage_Adapter_Ldap = array(
        'uri'    => null,
        'format' => null,
        'filter' => '\w',
    );

    /**
     *
     * Checks to make sure the LDAP extension is available.
     *
     * @return null
     *
     */
    protected function _preConfig()
    {
        parent::_preConfig();
        if (! extension_loaded('ldap')) {
            throw $this->_exception('ERR_EXTENSION_NOT_LOADED', array(
                'extension' => 'ldap',
            ));
        }
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

        // connect
        $conn = @ldap_connect($this->_config['uri']);

        // did the connection work?
        if (! $conn) {
            throw $this->_exception('ERR_CONNECTION_FAILED', $this->_config);
        }

        // upgrade to LDAP3 when possible
        @ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);

        // filter the username to prevent LDAP injection
        $regex = '/[^' . $this->_config['filter'] . ']/';
        $username = preg_replace($regex, '', $username);

        // bind to the server
        $rdn = sprintf($this->_config['format'], $username);
        $bind = @ldap_bind($conn, $rdn, $password);

        // did the bind succeed?
        if ($bind) {
            ldap_close($conn);
            return array('username' => $username);
        } else {
            $this->_err = @ldap_errno($conn) . " " . @ldap_error($conn);
            ldap_close($conn);
            return false;
        }
    }
}
