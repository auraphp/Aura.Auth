<?php
/**
 *
 * This file is part of Aura for PHP.
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
 * By default this binds directly as the user, using the `$dnformat` string to
 * build the bind DN from the username. Optionally, by passing a `$search`
 * configuration, it will instead use the "bind, search, rebind" pattern: bind
 * with a service account, search the directory for the user's real DN (which
 * may live anywhere in the tree), then rebind as that DN to verify the
 * password. The search also lets the adapter return the user's attributes.
 *
 * @package Aura.Auth
 *
 */
class LdapAdapter extends AbstractAdapter
{
    /**
     *
     * An LDAP server connection string.
     *
     * @var string
     *
     */
    protected $server;

    /**
     *
     * An sprintf() format string for the LDAP query. Used only for the direct
     * bind (when no `$search` configuration is given).
     *
     * @var string
     *
     */
    protected $dnformat = null;

    /**
     *
     * Set these options after the LDAP connection.
     *
     * @var array
     *
     */
    protected $options = array();

    /**
     *
     * Optional "bind, search, rebind" configuration. When non-empty, the
     * adapter binds with a service account and searches for the user instead
     * of binding directly. Keys:
     *
     * - `binddn`: the service-account DN used to bind for the search.
     * - `bindpw`: the service-account password.
     * - `basedn`: the base DN to search under.
     * - `filter`: an sprintf() format string for the search filter; the
     *   escaped username is substituted for `%s`, e.g. `(uid=%s)`.
     * - `attributes`: (optional) an array of attribute names to return; an
     *   empty array (the default) returns all attributes.
     *
     * @var array
     *
     */
    protected $search = array();

    /**
     *
     * An object to intercept PHP calls.
     *
     * @var Phpfunc
     *
     */
    protected $phpfunc;

    /**
     *
     * Constructor.
     *
     * @param Phpfunc $phpfunc An object to intercept PHP calls.
     *
     * @param string $server An LDAP server connection string.
     *
     * @param string $dnformat An sprintf() format string for the LDAP query
     * used by the direct bind. Ignored when `$search` is provided.
     *
     * @param array $options Set these options after the LDAP connection.
     *
     * @param array $search Optional "bind, search, rebind" configuration; see
     * the `$search` property.
     *
     */
    public function __construct(
        Phpfunc $phpfunc,
        $server,
        $dnformat,
        array $options = array(),
        array $search = array()
    ) {
        $this->phpfunc = $phpfunc;
        $this->server = $server;
        $this->dnformat = $dnformat;
        $this->options = $options;
        $this->search = $search;
    }

    /**
     *
     * Verifies a set of credentials.
     *
     * @param array $input The 'username' and 'password' to verify.
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

        if (! empty($this->search)) {
            return array(
                $username,
                $this->bindSearch($conn, $username, $password),
            );
        }

        $this->bind($conn, $username, $password);
        return array($username, array());
    }

    /**
     *
     * Connects to the LDAP server and sets options.
     *
     * @return resource The LDAP connection.
     *
     * @throws Exception\ConnectionFailed when the connection fails.
     *
     */
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

    /**
     *
     * Binds to the LDAP server directly with username and password, using the
     * `$dnformat` to build the bind DN.
     *
     * @param resource $conn The LDAP connection.
     *
     * @param string $username The input username.
     *
     * @param string $password The input password.
     *
     * @throws Exception\BindFailed when the username/password fails.
     *
     */
    protected function bind($conn, $username, $password)
    {
        $username = $this->escape($username);
        $bind_rdn = sprintf($this->dnformat, $username);

        $bound = $this->ldapBind($conn, $bind_rdn, $password);
        if (! $bound) {
            $error = $this->error($conn);
            $this->phpfunc->ldap_unbind($conn);
            throw new Exception\BindFailed($error);
        }

        $this->phpfunc->ldap_unbind($conn);
    }

    /**
     *
     * Binds with the service account, searches for the user, then rebinds as
     * the discovered user DN to verify the password.
     *
     * @param resource $conn The LDAP connection.
     *
     * @param string $username The input username.
     *
     * @param string $password The input password.
     *
     * @return array The discovered user's attributes.
     *
     * @throws Exception\BindFailed when the service-account bind or the user
     * rebind fails.
     *
     * @throws Exception\SearchFailed when the search operation itself fails.
     *
     * @throws Exception\UsernameNotFound when the search returns no entry.
     *
     * @throws Exception\MultipleMatches when the search returns more than one
     * entry.
     *
     */
    protected function bindSearch($conn, $username, $password)
    {
        // bind as the service account so we can search the directory
        $bound = $this->ldapBind(
            $conn,
            $this->search['binddn'],
            $this->search['bindpw']
        );
        if (! $bound) {
            $error = $this->error($conn);
            $this->phpfunc->ldap_unbind($conn);
            throw new Exception\BindFailed($error);
        }

        // search for the user to discover the real DN and attributes
        $filter = sprintf(
            $this->search['filter'],
            $this->escapeFilter($username)
        );
        $attributes = isset($this->search['attributes'])
            ? $this->search['attributes']
            : array();
        $result = $this->phpfunc->ldap_search(
            $conn,
            $this->search['basedn'],
            $filter,
            $attributes
        );

        // a false result is an operational failure (bad base DN, server error,
        // etc.), not a valid "no such user" outcome
        if ($result === false) {
            $error = $this->error($conn);
            $this->phpfunc->ldap_unbind($conn);
            throw new Exception\SearchFailed($error);
        }

        $entries = $this->phpfunc->ldap_get_entries($conn, $result);
        if ($entries === false) {
            $error = $this->error($conn);
            $this->phpfunc->ldap_unbind($conn);
            throw new Exception\SearchFailed($error);
        }

        // a successful search that matched nothing is a genuine "not found"
        if ($entries['count'] < 1) {
            $this->phpfunc->ldap_unbind($conn);
            throw new Exception\UsernameNotFound($username);
        }

        if ($entries['count'] > 1) {
            $this->phpfunc->ldap_unbind($conn);
            throw new Exception\MultipleMatches($username);
        }

        // rebind as the discovered user to verify the password
        $userdn = $entries[0]['dn'];
        $bound = $this->ldapBind($conn, $userdn, $password);
        if (! $bound) {
            $error = $this->error($conn);
            $this->phpfunc->ldap_unbind($conn);
            throw new Exception\BindFailed($error);
        }

        $this->phpfunc->ldap_unbind($conn);
        return $this->entryData($entries[0]);
    }

    /**
     *
     * Binds to the LDAP server, returning success as a boolean instead of
     * letting ldap_bind() emit a PHP warning on failure.
     *
     * A failed bind is an expected outcome (wrong password, missing user), and
     * we already report it to the caller through the BindFailed exception, so
     * the raw warning is redundant noise. Rather than reach for the `@`
     * error-suppression operator -- which hides *every* error for the call --
     * we install an error handler scoped to just this bind and restore it
     * immediately afterwards.
     *
     * @param resource $conn The LDAP connection.
     *
     * @param string $dn The bind DN.
     *
     * @param string $password The bind password.
     *
     * @return bool True if the bind succeeded, false otherwise.
     *
     */
    protected function ldapBind($conn, $dn, $password)
    {
        set_error_handler(static function () {
            // returning true marks the warning as handled, keeping the
            // failed-bind message out of the error log
            return true;
        });

        try {
            return (bool) $this->phpfunc->ldap_bind($conn, $dn, $password);
        } finally {
            restore_error_handler();
        }
    }

    /**
     *
     * Builds an "errno: error" string for the current connection.
     *
     * @param resource $conn The LDAP connection.
     *
     * @return string
     *
     */
    protected function error($conn)
    {
        return $this->phpfunc->ldap_errno($conn)
             . ': '
             . $this->phpfunc->ldap_error($conn);
    }

    /**
     *
     * Reduces a single ldap_get_entries() entry to a clean attribute map,
     * dropping the numeric/count bookkeeping keys. Single-valued attributes
     * become scalars; multi-valued attributes become arrays.
     *
     * @param array $entry One entry from ldap_get_entries().
     *
     * @return array
     *
     */
    protected function entryData(array $entry)
    {
        $data = array();
        for ($i = 0; $i < $entry['count']; $i++) {
            $attr = $entry[$i];
            $values = $entry[$attr];
            unset($values['count']);
            $data[$attr] = count($values) === 1
                ? $values[0]
                : array_values($values);
        }
        return $data;
    }

    /**
     *
     * Escapes input values for an LDAP DN string.
     *
     * Per <http://projects.webappsec.org/w/page/13246947/LDAP%20Injection>
     * and <https://www.owasp.org/index.php/Preventing_LDAP_Injection_in_Java>.
     *
     * @param string $str The string to be escaped.
     *
     * @return string The escaped string.
     *
     */
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

    /**
     *
     * Escapes input values for an LDAP search filter, per RFC 4515.
     *
     * @param string $str The string to be escaped.
     *
     * @return string The escaped string.
     *
     */
    protected function escapeFilter($str)
    {
        return strtr($str, array(
            '\\'   => '\\5c',
            '*'    => '\\2a',
            '('    => '\\28',
            ')'    => '\\29',
            "\x00" => '\\00',
        ));
    }
}
