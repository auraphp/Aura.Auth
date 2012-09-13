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
 * Authenticate against an IMAP or POP3 mail server.
 * 
 * @package Aura.Auth
 * 
 */
class Mail implements AuthInterface
{
    /**
     * 
     * @var Aura\Auth\User
     * 
     */
    protected $user;

    /**
     * 
     * @var string
     * 
     */
    protected $mailbox;


    /**
     *
     * @param Aura\Auth\User $user
     *     
     * @param string $mailbox An imap_open() mailbox string, for example
     *   "mail.example.com:143/imap" orp "mail.example.com:110/pop3"
     * 
     * @throws Aura\Auth\Exception If the extension 'imap' is not available.
     *
     */
    public function __construct(User $user, $mailbox)
    {
        if (! extension_loaded('imap')) {
            throw new Exception('The extension imap is not available.');
        }

        $this->mailbox = $mailbox;
        $this->user    = $user;
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

        $username = $opts['username'];
        $password = $opts['password'];
        $mailbox  = '{' . $this->mailbox . '}';
        $conn     = imap_open($mailbox, $username, $password, OP_HALFOPEN);

        if (is_resource($conn)) {
            @imap_close($conn);

            if (false === strpos($username, '@')) {
                $user['username'] = $username;
            } else {
                $tmp  = explode('@', $username);
                $user = ['username' => $tmp[0], 'email' => $username];
            }

            $user_obj = clone $this->user;
            $user_obj->setFromArray($user);

            return $user_obj;
        }

        return false;
    }
}

