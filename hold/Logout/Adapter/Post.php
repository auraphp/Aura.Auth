<?php
/**
 *
 * Logout protocol based on receiving HTTP POST parameters
 *
 * @package Aura.Auth
 *
 */
class Auth_Logout_Adapter_Post extends Solar_Auth_Logout_Adapter {

    /**
     *
     * Default configuration values.
     * @config string source_redirect Element key in the credential array source to indicate
     *   where to redirect on successful logout, default 'redirect'.
     *
     * @config string source_process Element key in the credential array source to indicate
     *   how to process the request, default 'process'.
     *
     * @config string process The source_process element value indicating a logout request;
     *   default is the 'PROCESS_LOGOUT' locale key value.
     *
     * @var array
     *
     */
    protected $_Solar_Auth_Logout_Adapter_Post = array(
        'source_process' => 'process',
        'source_redirect' => 'redirect',
        'process' => null,
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

        if (empty($this->_config['process'])) {
            $this->_config['process'] = $this->locale('PROCESS_LOGOUT');
        }
    }

    /**
     *
     * Tells if the current page load appears to be the result of
     * an attempt to log out.
     *
     * @return bool
     *
     */
    public function isLogoutRequest()
    {
        if ($this->_request->isCsrf()) {
            return false;
        }
        return $this->_request->post($this->_config['source_process']) == $this->_config['process'];
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
    public function getLogoutRedirect()
    {
        return $this->_request->post($this->_config['source_redirect']);
    }

}
