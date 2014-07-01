<?php
/**
 *
 * This file is part of the Aura project for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth;

/**
 *
 * Extension proxy for the ease of testing PHP functions
 *
 * Thank you Mike Naberezny
 *
 * http://mikenaberezny.com/2007/08/01/wrapping-php-functions-for-testability/
 *
 * @package Aura.Auth
 *
 */

class ExtensionProxy
{
    /**
     * ext
     *
     * @var mixed
     *
     */
    protected $ext;

    /**
     * __construct
     *
     * @param mixed $ext
     *
     */
    public function __construct($ext)
    {
        $this->ext = $ext;
    }

    /**
     * __call
     *
     * @param string $method
     *
     * @param bool $arguments
     *
     * @param mixed $args
     *
     */
    public function __call($method, $args)
    {
        return call_user_func_array("{$this->ext}_{$method}", $args);
    }
}
