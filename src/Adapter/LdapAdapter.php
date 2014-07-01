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
use Aura\Auth\Phpfunc;

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
    protected $server;
    protected $dnformat = null;
    protected $options = array();
    protected $phpfunc;

    public function __construct(
        Phpfunc $phpfunc,
        $server,
        $dnformat,
        array $options = array()
    ) {
        $this->phpfunc = $phpfunc;
        $this->server = $server;
        $this->dnformat = $dnformat;
        $this->options = $options;
    }

    /**
     *
     * Verifies set of credentials.
     *
     * @param array $input A list of credentials to verify
     *
     * @return mixed An array of verified user information, or boolean false
     * if verification failed.
     *
     */
    public function login(array $input)
    {
        $this->checkInput($input);
        $username = $input['username'];
        $password = $input['password'];

        $conn = $this->connect();
        $this->bind($conn, $username, $password);
        return array($username, array());
    }

    protected function connect()
    {
        $conn = $this->phpfunc->ldap_connect($this->server);
        if (! $conn) {
            throw new Exception\ConnectionFailed($this->server);
        }

        foreach ($this->options as $opt => $val) {
            $this->phpfunc->ldap_set_option($conn, $opt, $val);
        }

        return $conn;
    }

    protected function bind($conn, $username, $password)
    {
        $username = $this->escape($username);
        $bind_rdn = sprintf($this->dnformat, $username);

        $bound = $this->phpfunc->ldap_bind($conn, $bind_rdn, $password);
        if (! $bound) {
            $error = $this->phpfunc->ldap_errno($conn)
                   . ': '
                   . $this->phpfunc->ldap_error($conn);
            $this->phpfunc->ldap_close($conn);
            throw new Exception\BindFailed($error);
        }

        $this->phpfunc->ldap_unbind($conn);
        $this->phpfunc->ldap_close($conn);
    }

    // per http://projects.webappsec.org/w/page/13246947/LDAP%20Injection
    // and https://www.owasp.org/index.php/Preventing_LDAP_Injection_in_Java
    protected function escape($str)
    {
        return strtr($str, array(
            '\\' => '\\\\',
            '&'  => '\\&',
            '!'  => '\\!',
            '|'  => '\\|',
            '='  => '\\=',
            '<'  => '\\<',
            '>'  => '\\>',
            ','  => '\\,',
            '+'  => '\\+',
            '-'  => '\\-',
            '"'  => '\\"',
            "'"  => "\\'",
            ';'  => '\\;',
        ));
    }
}
