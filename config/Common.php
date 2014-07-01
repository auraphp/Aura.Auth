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
namespace Aura\Auth\_Config;

use Aura\Di\Config;
use Aura\Di\Container;

/**
 *
 * @package Aura.Auth
 *
 */
class Common extends Config
{
    public function define(Container $di)
    {
        /**
         * Services.
         */
        $di->set('aura_auth', $di->lazyNew('Aura\Auth\Auth'));
        $di->set('aura_auth_login_service', $di->lazyNew('Aura\Auth\Service\LoginService'));
        $di->set('aura_auth_logout_service', $di->lazyNew('Aura\Auth\Service\LogoutService'));
        $di->set('aura_auth_resume_service', $di->lazyNew('Aura\Auth\Service\ResumeService'));
        $di->set('aura_auth_session', $di->lazyNew('Aura\Auth\Session\Session'));
        $di->set('aura_auth_timer', $di->lazyNew('Aura\Auth\Session\Timer'));
        $di->set('aura_auth_hash_verifier', $di->lazyNew('Aura\Auth\Verifier\HashVerifier'));
        $di->set('aura_auth_password_verifier', $di->lazyNew('Aura\Auth\Verifier\PasswordVerifier'));
        $di->set('aura_auth_htpasswd_verifier', $di->lazyNew('Aura\Auth\Verifier\HtpasswdVerifier'));
        // $di->set('aura_auth_adapter', $di->lazyNew('Aura\Auth\Adapter'));

        /**
         * Aura\Auth\Auth
         */
        $di->params['Aura\Auth\Auth'] = array(
            'segment'  => $di->lazyNew('Aura\Auth\Session\Segment')
        );

        /**
         * Aura\Auth\Service\LoginService
         */
        $di->params['Aura\Auth\Service\LoginService'] = array(
            'adapter' => $di->lazyGet('aura_auth_adapter'),
            'session' => $di->lazyGet('aura_auth_session')
        );

        /**
         * Aura\Auth\Service\LogoutService
         */
        $di->params['Aura\Auth\Service\LogoutService'] = array(
            'adapter' => $di->lazyGet('aura_auth_adapter'),
            'session' => $di->lazyGet('aura_auth_session')
        );

        /**
         * Aura\Auth\Service\ResumeService
         */
        $di->params['Aura\Auth\Service\ResumeService'] = array(
            'adapter' => $di->lazyGet('aura_auth_adapter'),
            'session' => $di->lazyGet('aura_auth_session'),
            'timer' => $di->lazyGet('aura_auth_timer'),
            'logout_service' => $di->lazyGet('aura_auth_logout_service'),
        );

        /**
         * Aura\Auth\Session\Timer
         */
        $di->params['Aura\Auth\Session\Timer'] = array(
            'ini_gc_maxliftime' => ini_get('session.gc_maxlifetime'),
            'ini_cookie_liftime' => ini_get('session.cookie_lifetime'),
            'idle_ttl' => 1440,
            'expire_ttl' => 14400,
        );

        $di->params['Aura\Auth\Session\Session'] = array(
            'cookie' => $_COOKIE
        );

        /**
         * Aura\Auth\Verifier\HashVerifier
         */
        $di->params['Aura\Auth\Verifier\HashVerifier'] = array(
            'algo' => '',
            'salt' => null
        );

        /**
         * Aura\Auth\Verifier\PasswordVerifier
         */
        $di->params['Aura\Auth\Verifier\PasswordVerifier'] = array(
            'algo' => '',
            'opts' => array()
        );
    }
}
