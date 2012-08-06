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
 * Authenticate against an ini file.
 * 
 * @package Aura.Auth
 * 
 */
class Ini implements AuthInterface
{
    /**
     * 
     * @var string Path to ini file.
     * 
     */
    protected $file;

    /**
     * 
     * @var Aura\Auth\User
     * 
     */
    protected $user;


    /**
     * 
     * @param Aura\Auth\User $user
     * 
     * @param string $file The ini file to use for Auth.
     * 
     * @throws Aura\Auth\Exception If $file does not exist.
     * 
     */
    public function __construct(User $user, $file)
    {
        $this->user = $user;
        $this->file = realpath($file);

        // does the file exist?
        if (! file_exists($this->file) || ! is_readable($this->file)) {
            $msg = "File `{$this->file}` does not exist or is not readable.";
            throw new Exception($msg);
        }
    }

    /**
     * 
     * Auth a user.
     * 
     * @param array $opts An array containing the keys `username` and `password`.
     * 
     * @throws Aura\Auth\Exception If $opts does not contain the 
     * keys `username` and `password`.
     * 
     * @return Aura\Auth\User|boolean
     * 
     */
    public function authenticate(array $opts = [])
    {
        if (! isset($opts['username']) || ! isset($opts['password'])) {
            $msg = 'The option `username` and / or `password` is missing.';
            throw new Exception($msg);
        }

        if (empty($opts['username']) || empty($opts['password'])) {
            return false;
        }

        // parse the file into an array
        $data = parse_ini_file($this->file, true);
        
        // get user info for the handle
        $user = empty($data[$opts['username']]) ? [] : $data[$opts['username']];
        
        // there must be an entry for the username and a password
        if (empty($user['password'])) {
            return false;
        }

        $valid = false;

        // plain-text password match.
        if (empty($user['hash_algo'])) {

            $valid = ($user['password'] == $opts['password']);

        // there is a hash algorithm
        } else {

            $salt  = empty($user['hash_salt']) ? false : $user['hash_salt'];
            $hpass = $this->hash($user['hash_algo'], $opts['password'], $salt);
            $valid = ($user['password'] == $hpass);
        }

        if ($valid) {
            
            unset($user['hash_algo'], $user['hash_salt'], $user['password']);

            $user['username']  = $opts['username'];
            $user['unique_id'] = $opts['username'];
            $user_obj          = clone $this->user;
            $user_obj->setFromArray($user);

            return $user_obj;
        }

        return false;
    }

    /**
     *
     * Hash data with optional salt.
     *
     * @param string $algo The hashing algorithm
     * 
     * @param string $data The data to hash
     * 
     * @param string $salt The hashing salt
     *
     * @return string
     *
     */
    protected function hash($algo, $data, $salt)
    {
        if (! in_array($algo, hash_algos())) {
            $msg  = "Unknown or unavailable hash algorithm ({$algo}).";
            throw new Exception($msg);
        }

        if (! empty($salt)) {
            $data = "{$data}{$salt}";
        }

        return hash($algo, $data);
    }
}