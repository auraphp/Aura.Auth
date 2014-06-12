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
     * Columns to be selected.
     *
     * @param array
     *
     */
    protected $cols = array();

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
     * Constructor
     *
     * @param \PDO $pdo
     *
     * @param VerifierInterface $verifier
     *
     * @param array $cols
     *
     * @param string $from
     *
     * @param string $where
     *
     */
    public function __construct(
        PDO $pdo,
        VerifierInterface $verifier,
        array $cols,
        $from,
        $where = null
    ) {
        $this->pdo = $pdo;
        $this->verifier = $verifier;
        $this->cols = $cols;
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

        $verified = $this->verify($creds, $row);
        if (! $verified) {
            return false;
        }

        $this->info = $row;
        $this->user = $this->info['username'];
        unset($this->info['username']);
        unset($this->info['password']);
        return true;
    }

    /**
     *
     * @param array $creds
     *
     * @return bool
     *
     */
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

    /**
     *
     * Fetch a row from the table
     *
     * @param array $creds
     *
     * @return bool / row
     *
     */
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


    /**
     *
     * Build SQL query
     *
     * @return string
     *
     */
    protected function buildSelect()
    {
        $cols = $this->buildSelectCols();
        $from = $this->buildSelectFrom();
        $where = $this->buildSelectWhere();
        return "SELECT {$cols} FROM {$from} WHERE {$where}";
    }

    protected function buildSelectCols()
    {
        $cols = $this->cols;
        $cols[0] .= ' AS username';
        $cols[1] .= ' AS password';
        return implode(', ', $cols);
    }

    protected function buildSelectFrom()
    {
        return $this->from;
    }

    protected function buildSelectWhere()
    {
        $where = "username = :username";
        if ($this->where) {
            $where .= " AND ({$this->where})";
        }
        return $where;
    }

    /**
     *
     * Password verification
     *
     * @return bool
     *
     */
    protected function verify($creds, $row)
    {
        $verified = $this->verifier->verify(
            $creds['password'],
            $row['password'],
            $row
        );

        if (! $verified) {
            $this->error = 'Password incorrect.';
            return false;
        }

        return true;
    }

}
