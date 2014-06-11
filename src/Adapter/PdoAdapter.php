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

use PDO;

/**
 *
 * Authenticate against an SQL database table via PDO.
 *
 * @package Aura.Auth
 *
 */
class PdoAdapter extends AbstractAdapter
{
    /**
     *
     * A PDO connection object.
     *
     * @var PDO
     *
     */
    protected $pdo;

    /**
     *
     * The username column.
     *
     * @var string
     *
     */
    protected $username_col;

    /**
     *
     * The hashed-password column.
     *
     * @var string
     *
     */
    protected $password_col;

    /**
     *
     * Columns for additional user information.
     *
     * @param array
     *
     */
    protected $info_cols = array();

    /**
     *
     * Select FROM this table; add JOIN specifications here as needed.
     *
     * @var string
     *
     */
    protected $from;

    /**
     *
     * Added WHERE conditions for the select.
     *
     * @var string
     *
     */
    protected $where;

    /**
     *
     * The hash() algorithm to use for the password.
     *
     * @todo Make this an injection so we can support more/better hashing,
     * such as password_compat.
     *
     * @var string
     *
     */
    protected $hash_algo;

    /**
     *
     * A salt for the hash algo.
     *
     * @var string
     *
     * @todo Remove this when we inject the hashing system.
     *
     */
    protected $salt;

    public function __construct(
        PDO $pdo,
        array $cols,
        $from,
        $where = null,
        $hash_algo = 'md5',
        $salt = ''
    ) {
        $this->pdo = $pdo;
        $this->username_col = array_shift($cols);
        $this->password_col = array_shift($cols);
        $this->info_cols = $cols;
        $this->from = $from;
        $this->where = $where;
        $this->hash_algo = $hash_algo;
        $this->salt = $salt;
    }

    /**
     *
     * Log in with username/password credentials.
     *
     * @param array $creds An array of credential data, including any data to
     * bind to the query.
     *
     * @return bool True on success, false on failure.
     *
     */
    public function login($creds)
    {
        if (! $this->fixCredentials($creds)) {
            return false;
        }

        $row = $this->fetchRow($creds);
        if (! $row) {
            return false;
        }

        $this->info = $row;
        $this->user = $this->info['username'];
        unset($this->info['username']);
        return true;
    }

    protected function fixCredentials(&$creds)
    {
        if (empty($creds['username'])) {
            $this->error = 'Username empty.';
            return false;
        }

        if (empty($creds['password'])) {
            $this->error = 'Password empty.';
            return false;
        }

        $creds['password'] = hash(
            $this->hash_algo,
            $this->salt . $creds['password']
        );

        return true;
    }

    protected function fetchRow($creds)
    {
        $stm = $this->buildSelect();
        $sth = $this->pdo->prepare($stm);
        $sth->execute($creds);
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) < 1) {
            $this->error = 'Credentials failed.';
            return false;
        }

        if (count($rows) > 1) {
            $this->error = 'Duplicate credentials.';
            return false;
        }

        return $rows[0];
    }

    protected function buildSelect()
    {
        $cols = implode(', ', array_merge(
            array("{$this->username_col} AS username"),
            $this->info_cols
        ));

        $where = "username = :username AND {$this->password_col} = :password";
        if ($this->where) {
            $where .= " AND ({$this->where})";
        }

        return "SELECT {$cols} FROM {$this->from} WHERE {$where}";
    }
}
