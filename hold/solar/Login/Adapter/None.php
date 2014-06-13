<?php
/**
 *
 * Authenticate against nothing; defaults all authentication to "failed".
 *
 * @package Aura.Auth
 *
 */
class Auth_Login_Adapter_None extends Solar_Auth_Login_Adapter
{

    /**
     *
     * Default configuration values.
     *
     * @var array
     *
     */
    protected $_Solar_Auth_Login_Adapter_None = array(
    );


    /**
     *
     * Tells if the current page load appears to be the result of
     * an attempt to log in.
     *
     * @return bool
     *
     */
    public function isLoginRequest()
    {
        return false;
    }

    /**
     *
     * Loads the user credentials (username and password) from the request source.
     *
     * @return array List of authentication credentials
     *
     */
    public function getCredentials()
    {
        return false;
    }

    /**
     *
     * The login was success, complete the protocol
     *
     * @return null
     *
     */
    public function postLoginSuccess()
    {
    }

    /**
     *
     * The login was a failure, complete the protocol
     *
     * @return null
     *
     */
    public function postLoginFailure()
    {
    }

    /**
     *
     * Looks at the value of the 'redirect' source key, and determines a
     * redirection url from it.
     *
     * If the 'redirect' key is empty or not present, will not redirect, and
     * processing will continue.
     *
     * @return string|null The url to redirect to or null if no redirect
     *
     */
    public function getLoginRedirect()
    {
        return null;
    }

}
