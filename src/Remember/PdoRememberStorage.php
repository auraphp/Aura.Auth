<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Auth\Remember;

use PDO;

/**
 *
 * Stores "remember me" tokens in an SQL table via PDO.
 *
 * Expected table (adjust types to your database):
 *
 *     CREATE TABLE aura_auth_remember (
 *         selector         VARCHAR(32)  NOT NULL PRIMARY KEY,
 *         hashed_validator VARCHAR(64)  NOT NULL,
 *         username         VARCHAR(255) NOT NULL,
 *         userdata         TEXT         NULL,
 *         expires          INTEGER      NOT NULL
 *     );
 *     CREATE INDEX aura_auth_remember_username ON aura_auth_remember (username);
 *
 * @package Aura.Auth
 *
 */
class PdoRememberStorage implements RememberStorageInterface
{
    /**
     *
     * A PDO connection.
     *
     * @var PDO
     *
     */
    protected $pdo;

    /**
     *
     * The table holding the tokens.
     *
     * @var string
     *
     */
    protected $table;

    /**
     *
     * Constructor.
     *
     * @param PDO $pdo A PDO connection.
     *
     * @param string $table The table holding the tokens.
     *
     */
    public function __construct(PDO $pdo, $table = 'aura_auth_remember')
    {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function create($selector, $hashed_validator, $username, array $userdata, $expires): void
    {
        $stm = "INSERT INTO {$this->table} "
             . "(selector, hashed_validator, username, userdata, expires) "
             . "VALUES (:selector, :hashed_validator, :username, :userdata, :expires)";
        $sth = $this->pdo->prepare($stm);
        $sth->execute(array(
            'selector' => $selector,
            'hashed_validator' => $hashed_validator,
            'username' => $username,
            'userdata' => json_encode($userdata),
            'expires' => $expires,
        ));
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function findBySelector($selector): ?array
    {
        $stm = "SELECT selector, hashed_validator, username, userdata, expires "
             . "FROM {$this->table} WHERE selector = :selector";
        $sth = $this->pdo->prepare($stm);
        $sth->execute(array('selector' => $selector));
        $row = $sth->fetch(PDO::FETCH_ASSOC);

        if (! $row) {
            return null;
        }

        $userdata = json_decode((string) $row['userdata'], true);
        $row['userdata'] = is_array($userdata) ? $userdata : array();
        $row['expires'] = (int) $row['expires'];
        return $row;
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function update($selector, $hashed_validator, $expires): void
    {
        $stm = "UPDATE {$this->table} "
             . "SET hashed_validator = :hashed_validator, expires = :expires "
             . "WHERE selector = :selector";
        $sth = $this->pdo->prepare($stm);
        $sth->execute(array(
            'hashed_validator' => $hashed_validator,
            'expires' => $expires,
            'selector' => $selector,
        ));
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function deleteBySelector($selector): void
    {
        $stm = "DELETE FROM {$this->table} WHERE selector = :selector";
        $sth = $this->pdo->prepare($stm);
        $sth->execute(array('selector' => $selector));
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function deleteByUsername($username): void
    {
        $stm = "DELETE FROM {$this->table} WHERE username = :username";
        $sth = $this->pdo->prepare($stm);
        $sth->execute(array('username' => $username));
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function deleteExpired(): void
    {
        $stm = "DELETE FROM {$this->table} WHERE expires < :now";
        $sth = $this->pdo->prepare($stm);
        $sth->execute(array('now' => time()));
    }
}
