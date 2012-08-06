<?php
/**
 * 
 * This file is part of the Aura project for PHP.
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Auth\Adapter;

use Aura\Auth\Exception;
use Aura\Auth\User;

/**
 * 
 * Authenticate using a user defined closure.
 * 
 * @package Aura.Auth
 * 
 */

class Closure implements AuthInterface
{

    /**
     * 
     * @var Aura\Auth\User
     * 
     */
    protected $user;

    /**
     * 
     * @var \Closure
     * 
     */
    protected $closure;


    /**
     *
     *
     * @param 
     *
     * @return 
     *
     */
    public function __construct(User $user, $closure)
    {
        $this->user    = $user;

        if ($closure instanceOf \Closure 
            || (is_object($closure) && method_exists($closure, '__invoke'))) {
            
            $this->closure = $closure;

        } else {
            $msg = '$closure must be an anonymous function or an object ' .
                   'containing the method __invoke.';
            throw new Exception($msg);
        }
    }

    /**
     * 
     * Authenticate a user.
     * 
     * @param array $opts An array containing the keys `username` and `password`.
     * 
     * @throws Aura\Auth\Exception If $opts does not contain the 
     * keys `username` and `password`.
     * 
     * @throws Aura\Auth\Exception If the closure does not return an array or
     * false.
     * 
     * @return Aura\Auth\User|boolean
     * 
     */
    public function authenticate(array $opts = [])
    {
        if (! isset($opts['username']) || ! isset($opts['password'])) {
            $msg = 'The option `username` or `password` is missing.';
            throw new Exception($msg);
        }

        if (empty($opts['username']) || empty($opts['password'])) {
            return false;
        }

        $closure = $this->closure;
        $user    = $closure($opts['username'], $opts['password']);

        if (is_array($user)) {
            $user_obj = clone $this->user;
            $user_obj->setFromArray($user);

            return $user_obj;

        } else if (false === $user) {
            return false;
        }

        throw new Exception('The closure must return an array or false.');
    }
}