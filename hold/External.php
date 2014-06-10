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
namespace Aura\Auth;

/**
 *
 * Verifies that the credentials passed were verified by a third
 * party identity provider, such as typekey, facebook, open id, or
 * SAML.
 *
 * @package Aura.Auth
 *
 */
class Auth_Storage_Adapter_External extends Solar_Auth_Storage_Adapter
{
    /**
     *
     * Default configuration values.
     *
     * @var array
     *
     */
    protected $_Solar_Auth_Storage_Adapter_External = array(
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
        if (!empty($credentials['verified'])) {
            return $credentials;
        } else {
            return false;
        }
    }
}
