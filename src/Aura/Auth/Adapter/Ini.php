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
 * Authenticate against .ini style files.
 * 
 * Each group is a user handle, with keys for 'password'
 * and the optional keys: 'hash_algo', 'hash_salt', 'email', 'uri', 'avatar' and
 * 'full_name'.  For example ...
 * 
 *     [pmjones]
 *     password = plaintextpass
 * 
 *     # Optional values:
 * 
 *     hash_algo = sha512          # hashing algorithm to use on the password
 *     hash_salt = a_random_string # hash this users password with this salt. Format: hash_algo("{$password}{$hash_salt}")
 *     
 *     email     = pmjones@solarphp.com
 *     uri       = http://paul-m-jones.com/
 *     avatar    = http://paul-m-jones.com/avator.jpg
 *     full_name = Paul M. Jones
 * 
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
    protected $ini_file;

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
     */
    public function __construct(User $user, $file)
    {
        $this->user     = $user;
        $this->ini_file = realpath($file);
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
     * @return boolean
     * 
     */
    public function authenticate(array $opts)
    {
        if (! isset($opts['username']) || ! isset($opts['password'])) {
            $msg = 'The option `email` or `password` is missing.';
            throw new Exception($msg);
        }

        if (empty($opts['username'])) {
            return false;
        }

        if (empty($opts['password'])) {
            return false;
        }

        // does the file exist?
        if (! file_exists($this->ini_file) || ! is_readable($this->ini_file)) {
            $msg = "The ini file ({$this->ini_file}) is not readable.";
            throw new Exception($msg);
        }

        // parse the file into an array
        $data = parse_ini_file($this->ini_file, true);
        
        // get user info for the handle
        $user = empty($data[$opts['username']]) ? [] : $data[$opts['username']];
        
        // there must be an entry for the username and a password
        if (empty($user['password'])) {
            return false;
        }

        // plain-text password match.
        if (empty($user['hash_algo']) && $user['password'] == $passwd) {

            $valid = true;

        // there is a hash algorithm
        } else {

            $salt  = empty($user['hash_salt']) ? false : $user['hash_salt'];
            $hpass = hash($user['hash_algo'], $opts['password'], $salt);

            if ($user['password'] == $hpass) {

                $valid = true;
            } else {
                $valid = false;
            }
        } 

        if ($valid) {
            
            unset($user['hash_algo'], $user['hash_salt'], $user['password']);

            $user['username'] = $opts['username'];
            $user_obj         = clone $this->user;
            $user_obj->setFromArray($user);

            return $user;
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
     * @param string $algo The hashing salt
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

        if ($salt) {
            $data = $data . $salt;
        }

        return hash($algo, $data)
    }
}