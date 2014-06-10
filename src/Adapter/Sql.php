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
 * Authenticate against an SQL database table.
 *
 * @package Aura.Auth
 *
 */
class Auth_Storage_Adapter_Sql extends Solar_Auth_Storage_Adapter
{
    /**
     *
     * Default configuration values.
     *
     * @config dependency sql A Solar_Sql dependency injection.
     *
     * @config string table Name of the table holding authentication data.
     *
     * @config string username_col Name of the column with the user username ("username").
     *
     * @config string password_col Name of the column with the MD5-hashed password.
     *
     * @config string email_col Name of the column with the email address.
     *
     * @config string displayname_col Name of the column with the display name (displayname).
     *
     * @config string uri_col Name of the column with the website URI.
     *
     * @config string uid_col Name of the column with the numeric user ID ("user_id").
     *
     * @config string hash_algo The hashing algorithm for the password.  Default is 'md5'.
     *   See [[php::hash_algos() | ]] for a list of accepted algorithms.
     *
     * @config string salt A salt prefix to make cracking passwords harder.
     *
     * @config string|array where Additional _multiWhere() conditions to use
     *   when selecting rows for authentication.
     *
     * @config boolean auto_create Insert credentials into backend storage if verified by third
     *   party.
     *
     * @var array
     *
     */
    protected $_Solar_Auth_Storage_Adapter_Sql = array(
        'sql'         => 'sql',
        'table'       => 'members',
        'username_col'  => 'username',
        'password_col'  => 'password',
        'email_col'   => null,
        'displayname_col' => null,
        'uri_col'     => null,
        'uid_col'     => null,
        'hash_algo'   => 'md5',
        'salt'        => null,
        'where'       => array(),
        'auto_create' => false,
    );

    /**
     *
     * Return a list columns that represents the fetched user data
     *
     * @return array A list of columns to fetch.
     *
     */
    protected function _getCols()
    {
        // list of optional columns as (property => field)
        $optional = array(
            'email'   => 'email_col',
            'displayname' => 'displayname_col',
            'uri'     => 'uri_col',
            'uid'     => 'uid_col',
        );

        // always get the user username
        $cols = array($this->_config['username_col']);

        // get optional columns
        foreach ($optional as $key => $val) {
            if ($this->_config[$val]) {
                $cols[] = $this->_config[$val];
            }
        }
        return $cols;
    }

    /**
     *
     * Return a quoted reference to the username column
     *
     * @return string Handle column
     *
     */
    protected function _getHandleCol()
    {
        $username_col = $this->_config['username_col'];
        if (strpos($username_col, '.') === false) {
            $username_col = "{$this->_config['table']}.{$username_col}";
        }
        return $username_col;
    }

    /**
     *
     * Return a quoted reference to the password column
     *
     * @return string Passwd column
     *
     */
    protected function _getPasswdCol()
    {
        $password_col = $this->_config['password_col'];
        if (strpos($password_col, '.') === false) {
            $password_col = "{$this->_config['table']}.{$password_col}";
        }
        return $password_col;
    }

    /**
     *
     * Convert a row loaded from the database into a set of auth credentials.
     *
     * @param array $row The database row.
     *
     * @return array $info The converted credential set.
     *
     */
    protected function _convertRow($row)
    {
        $info = array();
        $cols = array(
            'username'  => 'username_col',
            'email'   => 'email_col',
            'displayname' => 'displayname_col',
            'uri'     => 'uri_col',
            'uid'     => 'uid_col',
        );
        foreach ($cols as $key => $val) {
            if ($this->_config[$val]) {
                $info[$key] = $row[$this->_config[$val]];
            }
        }

        // done
        return $info;
    }

    /**
     *
     * Stores a set of credentials
     *
     * @param array $credentials A list of credentials to store
     *
     * @return mixed An array of verified user information, or boolean false
     * if verification failed.
     *
     */
    public function _autoCreate($credentials)
    {
        $sql = Solar::dependency('Solar_Sql', $this->_config['sql']);

        $data = array();
        $cols = array(
            'username'  => 'username_col',
            'email'   => 'email_col',
            'displayname' => 'displayname_col',
            'uri'     => 'uri_col',
            );
        foreach ($cols as $key => $val) {
            if ($this->_config[$val] && !empty($credentials[$key])) {
                $data[$this->_config[$val]] = $credentials[$key];
            }
        }

        $data['status'] = 'active';

        $result = $sql->insert($this->_config['table'], $data);

        if ($result) {
            $credentials['uid'] = $sql->lastInsertId();
            return $credentials;
        } else {
            return false;
        }
    }

    /**
     *
     * Load a user based on a set of credentials
     *
     * @param array $credentials A list of credentials to verify
     *
     * @return mixed An array of verified user information, or boolean false
     * if verification failed.
     *
     */
    protected function _loadUser($credentials)
    {
        // get the dependency object of class Sql
        $obj = Solar::dependency('Solar_Sql', $this->_config['sql']);

        // get a selection tool using the dependency object
        $select = Solar::factory(
            'Solar_Sql_Select',
            array('sql' => $obj)
        );

        // build the select
        $select->from($this->_config['table'], $this->_getCols())
               ->where($this->_getHandleCol() . " = ?", $credentials['username'])
               ->multiWhere($this->_config['where'])
               ->limit(1);

        $row = $select->fetchOne();
        if ($row) {
            return $this->_convertRow($row);
        } else {
            if ($this->_config['auto_create']) {
                return $this->_autoCreate($credentials);
            }
            return false;
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

            // This is a password-less authentication
            if (!empty($credentials['verified'])) {
                return $this->_loadUser($credentials);
            }

            return false;
        }

        // get the dependency object of class Sql
        $obj = Solar::dependency('Solar_Sql', $this->_config['sql']);

        // get a selection tool using the dependency object
        $select = Solar::factory(
            'Solar_Sql_Select',
            array('sql' => $obj)
        );

        // salt and hash the password
        $hash = hash(
            $this->_config['hash_algo'],
            $this->_config['salt'] . $credentials['password']
        );

        // build the select, fetch up to 2 rows (just in case there's actually
        // more than one, we don't want to select *all* of them).
        $select->from($this->_config['table'], $this->_getCols())
               ->where($this->_getHandleCol() . " = ?", $credentials['username'])
               ->where($this->_getPasswdCol() . " = ?", $hash)
               ->multiWhere($this->_config['where'])
               ->limit(2);

        // get the results
        $rows = $select->fetchAll();

        // if we get back exactly 1 row, the user is authenticated;
        // otherwise, it's more or less than exactly 1 row.
        if (count($rows) == 1) {

            return $this->_convertRow(current($rows));

        } else {

            // User credentials are not valid
            return false;
        }
    }
}
