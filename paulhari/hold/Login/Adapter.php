<?php
/**
 *
 * Abstract Authentication Login Protocol.
 *
 * @package Aura.Auth
 *
 */
abstract class Auth_Login_Adapter
{

    /**
     *
     * Details on the current request.
     *
     * @var Solar_Request
     *
     */
    protected $_request;

    /**
     *
     * Post-construction tasks to complete object construction.
     *
     * @return null
     *
     */
    protected function _postConstruct()
    {
        parent::_postConstruct();

        // get the current request environment
        $this->_request = Solar_Registry::get('request');
    }

    /**
     *
     * Returns this protocol object.
     *
     * @return Solar_Auth_Login_Adapter
     *
     */
    public function getProtocol()
    {
        return $this;
    }

    /**
     *
     * Tells if the current page load appears to be the result of
     * an attempt to log in.
     *
     * @return bool
     *
     */
    abstract public function isLoginRequest();

    /**
     *
     * Loads the user credentials (username and password) from the request source.
     *
     * @return array List of authentication credentials
     *
     */
    abstract public function getCredentials();

    /**
     *
     * The login was success, complete the protocol
     *
     * @return null
     *
     */
    abstract public function postLoginSuccess();

    /**
     *
     * The login was a failure, complete the protocol
     *
     * @return null
     *
     */
    abstract public function postLoginFailure();

    /**
     *
     * Determine the location to redirect to after successful login
     *
     * @return string|null The url to redirect to or null if no redirect
     *
     */
    abstract public function getLoginRedirect();

}