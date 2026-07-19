<?php
/**
 *
 * This file is part of the Aura project for PHP.
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Auth;

/**
 *
 * Proxy for the ease of testing PHP functions.
 *
 * http://mikenaberezny.com/2007/08/01/wrapping-php-functions-for-testability/
 *
 * @package Aura.Auth
 *
 */

class Phpfunc
{
    /**
     *
     * Magic call for PHP functions.
     *
     * @param string $method The PHP function to call.
     *
     * @param array $params Params to pass to the function.
     *
     * @return mixed
     *
     */
    public function __call($method, $params): mixed
    {
        return call_user_func_array($method, $params);
    }
}
