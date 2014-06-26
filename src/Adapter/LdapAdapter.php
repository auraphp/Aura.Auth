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

use Aura\Auth\Exception\ExtensionNotLoaded;
/**
 *
 * Authenticate against an LDAP server.
 *
 * @package Aura.Auth
 *
 */
class LdapAdapter extends AbstractAdapter
{
    /**
     *
     * @var string $uri
     *
     */
    protected $uri;

    /**
     *
     * @var int $port
     *
     */
    protected $port;

    /**
     *
     * @var string $format
     *
     */
    protected $format;

    /**
     *
     * @var string $filter
     *
     */
    protected $filter;

    /**
     *
     * Checks to make sure the LDAP extension is available.
     *
     * @param string $uri URL to the LDAP server, for example "ldaps://example.com:389".
     *
     * @param int $port
     *
     * @param string $format Sprintf() format string for the LDAP query; %s
     *   represents the username.  Example: "uid=%s,dc=example,dc=com".
     *
     * @param string $filter A regular-expression snippet that lists allowed characters
     *   in the username.  This is to help prevent LDAP injections.  Default
     *   expression is '\w' (that is, only word characters are allowed).
     *
     * @throw ExtensionNotLoaded
     *
     * @return null
     *
     */
    public function __construct($uri = null, $port = 389, $format = null, $filter = '\w')
    {
        if (! extension_loaded('ldap')) {
            throw new ExtensionNotLoaded('Missing ldap extension');
        }
        $this->uri = $uri;
        $this->port = $port;
        $this->format = $format;
        $this->filter = $filter;
    }

    /**
     *
     * Verifies set of credentials.
     *
     * @param array $cred A list of credentials to verify
     *
     */
    public function login(array $cred)
    {
        $this->checkCredentials($cred);
        $username = $cred['username'];
        $password = $cred['password'];
        $this->connect($cred);
        return array($username, array());
    }
    /**
     *
     * Verifies set of credentials.
     *
     * @param array $cred A list of credentials to verify
     *
     * @return mixed An array of verified user information, or boolean false
     * if verification failed.
     *
     */
    public function connect($cred)
    {
        $username = $cred['username'];
        $password = $cred['password'];

        // connect
        $conn = ldap_connect($this->uri);

        // did the connection work?
        if (! $conn) {
            throw ConnectionFailed('Ldap failed to connect');
        }

        // upgrade to LDAP3 when possible
        if (ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3)) {
            throw ConnectionFailed('Failed to set the protocol version');
        }

        // filter the username to prevent LDAP injection
        $regex = '/[^' . $this->filter . ']/';
        $username = preg_replace($regex, '', $username);

        // bind to the server
        $rdn = sprintf($this->format, $username);
        $bind = ldap_bind($conn, $rdn, $password);

        // did the bind succeed?
        if ($bind) {
            ldap_close($conn);
        } else {
            $message = ldap_errno($conn) . " " . ldap_error($conn);
            ldap_close($conn);
            throw Exception($message);
        }
    }
}
