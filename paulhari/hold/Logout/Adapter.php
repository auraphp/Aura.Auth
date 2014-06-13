<?php
/**
 *
 * Abstract Authentication Logout Protocol.
 *
 * @package Aura.Auth
 *
 */
abstract class Auth_Logout_Adapter
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
     * @return Solar_Auth_Logout_Adapter
     *
     */
    public function getProtocol()
    {
        return $this;
    }

    /**
     *
     * Tells if the current page load appears to be the result of
     * an attempt to log out.
     *
     * @return bool
     *
     */
    abstract public function isLogoutRequest();

    /**
     *
     * Determine the location to redirect to after logout
     *
     * @return string|null The url to redirect to or null if no redirect
     *
     */
    abstract function getLogoutRedirect();

}