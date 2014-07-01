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
 * Authenticate against an LDAP server.
 *
 * @package Aura.Auth
 *
 */
class LdapAdapter extends AbstractAdapter
{
    /**
     *
     * Default configuration values.
     *
     * @config string uri URL to the LDAP server, for example "ldaps://example.com:389".
     *
     * @config string format Sprintf() format string for the LDAP query; %s
     *   represents the username.  Example: "uid=%s,dc=example,dc=com".
     *
     * @config string filter A regular-expression snippet that lists allowed characters
     *   in the username.  This is to help prevent LDAP injections.  Default
     *   expression is '\w' (that is, only word characters are allowed).
     *
     * @var array
     *
     */
    protected $uri = null;
    protected $format = null;
    protected $filter = '\w';
    protected $options = array();

    protected $proxy;

    public function __construct(
        FunctionProxy $proxy,
        $uri,
        $format,
        $filter = '\w',
        array $options = array()
    ) {
        $this->proxy = $proxy;
        $this->uri = $uri;
        $this->format = $format;
        $this->filter = $filter;
        $this->options = $options;
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
    public function login(array $cred)
    {
        $this->checkCredentials($cred);
        $username = $cred['username'];
        $password = $cred['password'];

        $conn = $this->proxy->ldap_connect($this->uri);
        if (! $conn) {
            throw new Exception\ConnectionFailed($this->uri);
        }

        foreach ($this->options as $opt => $val) {
            $this->proxy->ldap_set_option($conn, $opt, $val);
        }

        // filter the username to prevent LDAP injection
        $regex = '/[^' . $this->filter . ']/';
        $username = preg_replace($regex, '', $username);

        // bind to the server
        $rdn = sprintf($this->format, $username);
        $bind = $this->proxy->ldap_bind($conn, $rdn, $password);

        // did the bind succeed?
        if ($bind) {
            $this->proxy->ldap_close($conn);
            return array('username' => $username);
        }

        $e = new Exception\ConnectionFailed(
            $this->proxy->ldap_errno($conn) . ': ' . $this->proxy->ldap_error($conn)
        );
        $this->proxy->ldap_close($conn);
        throw $e;
    }
}
