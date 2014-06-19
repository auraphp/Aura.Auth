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
namespace Aura\Auth\Session;

/**
 *
 * @package Aura.Auth
 *
 */
interface SessionDataInterface
{
    /**
     *
     * Getter
     *
     * @param mixed $key
     *
     */
    public function __get($key);


    /**
     *
     * Setter
     *
     * @param mixed $key
     *
     * @param mixed $val
     *
     */
    public function __set($key, $val);

    /**
     * __isset
     *
     * @param mixed $key
     *
     * @return bool
     *
     */
    public function __isset($key);

    /**
     * __unset
     *
     * @param mixed $key
     *
     */
    public function __unset($key);
}
