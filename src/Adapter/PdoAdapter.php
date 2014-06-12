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
use Aura\Auth\Verifier\VerifierInterface;

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
     * A callable to verify passwords.
     *
     * @var callable
     *
     */
    protected $verifier;

    public function __construct(
        PDO $pdo,
        VerifierInterface $verifier,
        array $cols,
        $from,
        $where = null
    ) {
        $this->pdo = $pdo;
        $this->verifier = $verifier;
        $this->username_col = array_shift($cols);
        $this->password_col = array_shift($cols);
        $this->info_cols = $cols;
        $this->from = $from;
        $this->where = $where;
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
        if (! $this->checkCredentials($creds)) {
            return false;
        }

        $row = $this->fetchRow($creds);
        if (! $row) {
            return false;
        }

        $verified = $this->verifyPassword($creds, $row);
        if (! $verified) {
            return false;
        }

        $this->info = $row;
        $this->user = $this->info['username'];
        unset($this->info['username']);
        unset($this->info['password']);
        return true;
    }

    protected function checkCredentials(&$creds)
    {
        if (empty($creds['username'])) {
            $this->error = 'Username empty.';
            return false;
        }

        if (empty($creds['password'])) {
            $this->error = 'Password empty.';
            return false;
        }

        return true;
    }

    protected function fetchRow($creds)
    {
        $stm = $this->buildSelect();
        $sth = $this->pdo->prepare($stm);
        unset($creds['password']);
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
            array(
                "{$this->username_col} AS username",
                "{$this->password_col} AS password",
            ),
            $this->info_cols
        ));

        $where = "username = :username";
        if ($this->where) {
            $where .= " AND ({$this->where})";
        }

        return "SELECT {$cols} FROM {$this->from} WHERE {$where}";
    }

    protected function verifyPassword($creds, $row)
    {
        $verified = $this->verifier->verifyPassword($creds['password'], $row['password'], $row);
        if (! $verified) {
            $this->error = 'Password incorrect.';
            return false;
        }

        return true;
    }

}
