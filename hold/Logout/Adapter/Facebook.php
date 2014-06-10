<?php
/**
 *
 * Logout protocol based on receiving HTTP POST or GET, resets the FB cookie
 *
 * @package Aura.Auth
 *
 * @author Richard Thomas <richard@phpjack.com>
 *
 */
class Auth_Logout_Adapter_Facebook extends Solar_Auth_Logout_Adapter {

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
     * @config string method Is this a post or a get request
     *
     * @config dependency facebook A dependency on a Facebook instance;
     *  default is a Solar_Registry entry named 'facebook'.
     *
     * @var array
     *
     */
    protected $_Solar_Auth_Logout_Adapter_Facebook = array(
        'source_process' => 'process',
        'source_redirect' => 'redirect',
        'process' => null,
        'method'  => 'post',
        'facebook' => 'facebook',
    );

    /**
     *
     * A Facebook library instance.
     *
     * @var Facebook
     *
     */
    protected $_facebook;

    /**
     *
     * Set up the dependency to the Facebook object.
     *
     * @return null
     *
     */
    protected function _postConstruct()
    {
        parent::_postConstruct();

        $this->_facebook = Solar::dependency(
            'Facebook',
            $this->_config['facebook']
        );
    }

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
     * an attempt to log out, if so resets the facebook cookie
     *
     * @return bool
     *
     */
    public function isLogoutRequest()
    {
        if ($this->_request->isCsrf()) {
            return false;
        }
        $method = $this->_config['method'];
        if ($this->_request->$method($this->_config['source_process']) == $this->_config['process']) {
            setcookie('fbs_'.$this->_facebook->getAppId(), "", time() - 36000);
            return true;
        }
        return false;
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
        $method = $this->_config['method'];
        return $this->_request->$method($this->_config['source_redirect']);
    }

}
