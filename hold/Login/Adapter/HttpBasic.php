<?php
/**
 *
 * Login protocol implementing HTTP Auth Login.
 *
 * @package Aura.Auth
 *
 */
class Auth_Login_Adapter_HttpBasic extends Solar_Auth_Login_Adapter {

    /**
     *
     * Default configuration values.
     *
     * @config string realm Realm to use in the auth challenge
     *
     * @config bool force Force HTTP authentication; if not authenticated,
     * issue a 401 response code to force the user to enter credentials.
     *
     * @var array
     *
     */
    protected $_Solar_Auth_Login_Adapter_HttpBasic = array(
        'realm' => 'Secure Area',
        'force' => false,
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
        $result = $this->_request->server('PHP_AUTH_USER')
               && $this->_request->server('PHP_AUTH_PW');

        if (! $result && $this->_config['force']) {
            $this->_forceLogin();
        } else {
            return $result;
        }
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
        // retrieve the username and password
        $username = $this->_request->server('PHP_AUTH_USER');
        $password = $this->_request->server('PHP_AUTH_PW');

        return array('username'=> $username, 'password' => $password);
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
        // HTTP Auth doesn't support redirecting after first authentication
        return null;
    }

    /**
     *
     * Forces the user to log in by presenting an authentication dialog in the
     * browser.
     *
     * @return null
     *
     */
    protected function _forceLogin()
    {
        $response = Solar_Registry::get('response');
        $label = 'WWW-Authenticate';
        $value = 'Basic realm="' . $this->_config['realm'] . '"';
        $response->setHeader($label, $value);
        $response->setStatusCode(401);
        $response->display();
        exit(0);
    }
}
