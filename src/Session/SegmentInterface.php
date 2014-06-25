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
interface SegmentInterface
{
    /**
     *
     * Getter
     *
     * @param mixed $key
     *
     * @param mixed $alt Useful when you want to return if the key is null
     *
     */
    public function get($key, $alt = null);

    /**
     *
     * Setter
     *
     * @param mixed $key
     *
     * @param mixed $val
     *
     */
    public function set($key, $val);
}
