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
use Aura\Auth\Exception;
use Aura\Auth\Auth;

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
     * @return self
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
        $this->setCols($cols);
        $this->from = $from;
        $this->where = $where;
    }

    protected function setCols($cols)
    {
        if (! isset($cols[0]) || trim($cols[0] == '')) {
            throw new Exception\UsernameColumnNotSpecified;
        }
        if (! isset($cols[1]) || trim($cols[1] == '')) {
            throw new Exception\PasswordColumnNotSpecified;
        }
        $this->cols = $cols;
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
        $data = $this->fetchRow($cred);
        $this->verify($cred, $data);
        $name = $data['username'];
        unset($data['username']);
        unset($data['password']);
        return array($name, $data);
    }

    /**
     *
     * Fetch a row from the table
     *
     * @param array $cred
     *
     * @return array
     *
     */
    protected function fetchRow($cred)
    {
        $stm = $this->buildSelect();
        $rows = $this->fetchRows($stm, $cred);

        if (count($rows) < 1) {
            throw new Exception\UsernameNotFound;
        }

        if (count($rows) > 1) {
            throw new Exception\MultipleMatches;
        }

        return $rows[0];
    }

    /**
     * Fetch Rows
     *
     * @param mixed $stm
     *
     * @param mixed $bind
     *
     * @return array
     *
     */
    protected function fetchRows($stm, $bind)
    {
        $sth = $this->pdo->prepare($stm);
        unset($bind['password']);
        $sth->execute($bind);
        return $sth->fetchAll(PDO::FETCH_ASSOC);
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

    /**
     *
     * Build Select Cols
     *
     * @return string
     *
     */
    protected function buildSelectCols()
    {
        $cols = $this->cols;
        $cols[0] .= ' AS username';
        $cols[1] .= ' AS password';
        return implode(', ', $cols);
    }

    /**
     * Get the select from
     *
     * @return string
     *
     */
    protected function buildSelectFrom()
    {
        return $this->from;
    }

    /**
     * Build where
     *
     * @return string
     */
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
    protected function verify($cred, $data)
    {
        $verified = $this->verifier->verify(
            $cred['password'],
            $data['password'],
            $data
        );

        if (! $verified) {
            throw new Exception\PasswordIncorrect;
        }

        return true;
    }
}
