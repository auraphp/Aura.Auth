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

use Aura\Auth\Exception;
use Aura\Auth\Verifier\VerifierInterface;
use Aura\Auth\User;
/**
 *
 * Authenticate against .ini style files.
 *
 * Each group is a user username, with keys for 'password', 'displayname', 'email',
 * and 'uri'.  For example ...
 *
 *     [pmjones]
 *     password = plaintextpass
 *     email = pmjones@solarphp.com
 *     displayname = Paul M. Jones
 *     uri = http://paul-m-jones.com/
 *
 * @package Aura.Auth
 *
 */
class IniAdapter extends AbstractAdapter
{
    /**
     *
     * @var string
     *
     */
    protected $file;

    /**
     *
     * @param string $file
     *
     * @param VerifierInterface $verifier
     *
     * @return void
     */
    public function __construct($file, VerifierInterface $verifier)
    {
        $this->file = $file;
        $this->verifier = $verifier;
    }

    /**
     *
     * Verifies set of credentials.
     *
     * @param User $user
     *
     * @param array $cred A list of credentials to verify
     *
     */
    public function login(array $cred)
    {
        $this->checkCredentials($cred);
        $username = $cred['username'];
        $password = $cred['password'];
        $user_data = $this->fetchUser($username);
        $encrypted = $user_data['password'];
        unset($user_data['password']);
        $this->verify($password, $encrypted);
        return array($username, $user_data);
    }

    /**
     *
     * Verifies set of credentials.
     *
     * @param string $username
     *
     * @return mixed An array of verified user information
     *
     */
    public function fetchUser($username)
    {
        // force the full, real path to the .ini file
        $real = realpath($this->file);
        if (! $real) {
            throw new Exception\FileNotReadable($this->file);
        }

        // parse the file into an array
        $data = parse_ini_file($real, true);

        // get user info for the username
        $user = (! empty($data[$username])) ? $data[$username] : array();

        // did we find the encrypted password for the username?
        if ($user) {
            return $user;
        }

        throw new Exception\UsernameNotFound;
    }

    protected function verify($password, $encrypted)
    {
        if (! $this->verifier->verify($password, $encrypted)) {
            throw new Exception\PasswordIncorrect;
        }
    }
}
