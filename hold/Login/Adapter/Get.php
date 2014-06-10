<?php
/**
 *
 * Login protocol based on passing username credentials via URI parameters.
 * Probably not a very good idea, really.
 *
 * @package Aura.Auth
 *
 */
class Auth_Login_Adapter_Get extends Solar_Auth_Login_Adapter {

    /**
     *
     * Default configuration values.
     * @config string source_username Username key in the credential array source,
     *   default 'username'.
     *
     * @config string source_password Password key in the credential array source,
     *   default 'password'.
     *
     * @config string source_redirect Element key in the credential array source to indicate
     *   where to redirect on successful login, default 'redirect'.
     *
     * @config string source_process Element key in the credential array source to indicate
     *   how to process the request, default 'process'.
     *
     * @config string process The source_process element value indicating a login request;
     *   default is the 'PROCESS_LOGIN' locale key value.
     *
     * @var array
     *
     */
    protected $_Solar_Auth_Login_Adapter_Get = array(
        'source_username'  => 'username',
        'source_password'  => 'password',
        'source_process' => 'process',
        'source_redirect' => 'redirect',
        'process'  => null,
    );

    /**
     *
     * Modifies $this->_config after it has been built.
     *
     * @return null
     *
     */
    protected function _postConfig()
    {
        parent::_postConfig();

        // make sure we have process values
        if (empty($this->_config['process'])) {
            $this->_config['process'] = $this->locale('PROCESS_LOGIN');
        }

    }

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
        if ($this->_request->isCsrf()) {
            return false;
        }
        return $this->_request->get($this->_config['source_process']) == $this->_config['process'];
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
        $username = $this->_request->get($this->_config['source_username']);
        $password = $this->_request->get($this->_config['source_password']);

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
        return $this->_request->get($this->_config['source_redirect']);
    }

}
