<?php
/**
 *
 * Logout protocol adapter factory.
 *
 * @package Aura.Auth
 *
 */
class Auth_Logout extends Solar_Factory {

    /**
     *
     * Default configuration values.
     *
     * @config string adapter The adapter class, for example 'Solar_Access_Adapter_Open'.
     *
     * @var array
     *
     */
    protected $_Solar_Auth_Logout = array(
        'adapter' => 'Solar_Auth_Logout_Adapter_Post',
    );
}
