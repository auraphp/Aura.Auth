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

use Aura\Auth\Exception\AlgorithmNotAvailable;
use Aura\Auth\Exception\MissingUsernameOrPassword;

/**
 *
 * Authenticate against an ini file.
 *
 * @package Aura.Auth
 *
 */
class Ini extends FileAdapter
{

    /**
     *
     * Auth a user.
     *
     * @throws Aura\Auth\Exception If $opts does not contain the
     * keys `username` and `password`.
     *
     * @return boolean
     *
     */
    public function authenticate()
    {
        if (! isset($this->username) || ! isset($this->password)) {
            throw new MissingUsernameOrPassword();
        }

        if (empty($this->username) || empty($this->password)) {
            return false;
        }

        // parse the file into an array
        $data = parse_ini_file($this->file, true);

        // get user info for the handle
        $user = empty($data[$this->username]) ? array() : $data[$this->username];

        // there must be an entry for the username and a password
        if (empty($user['password'])) {
            return false;
        }

        $valid = false;

        // plain-text password match.
        if (empty($user['hash_algo'])) {
            $valid = ($user['password'] == $this->password);
        } else {
            // there is a hash algorithm
            $salt  = empty($user['hash_salt']) ? false : $user['hash_salt'];
            $hpass = $this->hash($user['hash_algo'], $this->password, $salt);
            $valid = ($user['password'] == $hpass);
        }

        if ($valid) {
            // @todo
            // set the user who is authenticated against, email and details
            // $this->details = $user;
            return true;
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
            throw new AlgorithmNotAvailable();
        }

        if (! empty($salt)) {
            $data = "{$data}{$salt}";
        }

        return hash($algo, $data);
    }
}
