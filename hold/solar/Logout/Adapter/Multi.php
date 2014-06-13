<?php
/**
 *
 * Logout protocol based on http GET request.
 *
 * @package Aura.Auth
 *
 */
class Auth_Logout_Adapter_Multi extends Solar_Auth_Logout_Adapter {

    /**
     *
     * Default configuration values.
     *
     * @config array adapters An array of logout dependency objects, one for
     * each of the logout protocols to be used.
     *
     * @var array
     *
     */
    protected $_Solar_Auth_Logout_Adapter_Multi = array(
        'adapters' => array(),
    );

    /**
     *
     * An array of adapter dependencies, one for each of the logout protocols
     * to be used.
     *
     * @var array
     *
     */
    protected $_adapters;

    /**
     *
     * The current logout protocol being used.
     *
     * @var Solar_Auth_Adapter_Logout
     *
     */
    protected $_protocol;

    /**
     *
     * Modifies $this->_config after it has been built.
     *
     * @return null
     *
     */
    protected function _postConstruct()
    {
        $this->_adapters = (array) $this->_config['adapters'];
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
        // have we already set a protocol?
        if ($this->_protocol) {
            return $this->_protocol->isLogoutRequest();
        }

        // go through the adapters to find a protocol
        foreach ($this->_adapters as $key => $spec) {

            // get the adapter protocol as a dependency
            $protocol = Solar::dependency('Solar_Auth_Logout', $spec);

            // retain the dependency
            $this->_adapter[$key] = $protocol;

            // does it recognize a logout request?
            if ($protocol->isLogoutRequest()) {
                // yes, retain the protocol
                $this->_protocol = $protocol;
                return true;
            }
        }

        // no protocol found
        return null;
    }

    /**
     *
     * Returns the current protocol object being used.
     *
     * @return Solar_Auth_Logout_Adapter
     *
     */
    public function getProtocol()
    {
        return $this->_protocol;
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
        return $this->_protocol->getLogoutRedirect();
    }

}
