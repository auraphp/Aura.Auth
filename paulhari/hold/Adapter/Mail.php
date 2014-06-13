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

/**
 *
 * Authenticate against an IMAP or POP3 mail server.
 *
 * @package Aura.Auth
 *
 */
class Mail extends AbstractAdapter
{
    /**
     *
     * Default configuration values.
     *
     * @config string mailbox An imap_open() mailbox string, for example
     *   "mail.example.com:143/imap" or "mail.example.com:110/pop3".
     *
     * @var array
     *
     */
    protected $_Solar_Auth_Storage_Adapter_Mail = array(
        'mailbox' => null,
    );

    /**
     *
     * Checks to make sure the IMAP extension is available.
     *
     * @return null
     *
     */
    protected function _preConfig()
    {
        parent::_preConfig();
        if (! extension_loaded('imap')) {
            throw $this->_exception('ERR_EXTENSION_NOT_LOADED', array(
                'extension' => 'imap',
            ));
        }
    }

    /**
     *
     * Verifies set of credentials.
     *
     * @param array $credentials A list of credentials to verify
     *
     * @return mixed An array of verified user information, or boolean false
     * if verification failed.
     *
     */
    public function validateCredentials($credentials)
    {
        if (empty($credentials['username'])) {
            return false;
        }
        if (empty($credentials['password'])) {
            return false;
        }
        $username = $credentials['username'];
        $password = $credentials['password'];

        $mailbox = '{' . $this->_config['mailbox'] . '}';
        $conn = @imap_open($mailbox, $username, $password, OP_HALFOPEN);
        if (is_resource($conn)) {
            @imap_close($conn);
            return array('username' => $username);
        } else {
            return false;
        }
    }
}
