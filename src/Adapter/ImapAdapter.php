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
use Aura\Auth\FunctionProxy;

/**
 *
 * Authenticate against an IMAP, POP3, or NNTP server.
 *
 * @package Aura.Auth
 *
 */
class ImapAdapter extends AbstractAdapter
{
    /**
     *
     * An imap_open() mailbox string; e.g., "{mail.example.com:143/imap/secure}"
     * or "{mail.example.com:110/pop3/secure}".
     *
     * @var string
     *
     */
    protected $mailbox;

    protected $options;

    protected $retries;

    protected $params;

    protected $proxy;

    public function __construct(
        FunctionProxy $proxy,
        $mailbox,
        $options = 0,
        $attempt = 1,
        array $params = null
    ) {
        $this->proxy = $proxy;
        $this->mailbox = $mailbox;
        $this->options = $options;
        $this->attempt = $attempt;
        $this->params = $params;
    }

    /**
     *
     * Log in with username/password credentials.
     *
     * @param array $cred An array of credential data, including any data to
     * bind to the query.
     *
     * @return bool True on success, false on failure.
     *
     */
    public function login(array $cred)
    {
        $this->checkCredentials($cred);
        $username = $cred['username'];
        $password = $cred['password'];

        $conn = $this->proxy->imap_open(
            $this->mailbox,
            $username,
            $password,
            $this->options,
            $this->attempt,
            $this->params
        );

        if (! $conn) {
            throw new Exception\ConnectionFailed($this->mailbox);
        }

        $this->proxy->imap_close($conn);
        return array($username, array());
    }
}
