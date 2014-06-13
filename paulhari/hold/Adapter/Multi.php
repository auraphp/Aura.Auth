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
 * Verifies that the credentials passed were verified by a third
 * Party identity provider, such as typekey, facebook, open id, or
 * SAML.
 *
 * @package Aura.Auth
 *
 */
class MultiAdapter extends AbstractAdapter
{
    /**
     *
     * Default configuration values.
     *
     * @config array adapters An array of storage dependency objects, one for
     * each of the storage systems to be used.
     *
     * @var array
     *
     */
    protected $_Solar_Auth_Storage_Adapter_Multi = array(
        'adapters' => array(),
    );

    /**
     *
     * An array of adapter dependencies, one for each of the storage systems
     * to be used.
     *
     * @var array
     *
     */
    protected $_adapters;

    /**
     *
     * Post-construction tasks.
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
        foreach ($this->_adapters as $adapter) {
            $result = $adapter->validateCredentials();
            if ($result) {
                return $result;
            }
        }

        return null;
    }
}
