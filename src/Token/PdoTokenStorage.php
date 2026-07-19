<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Auth\Token;

use PDO;

/**
 *
 * Stores API tokens in an SQL table via PDO.
 *
 * Expected table (adjust types to your database):
 *
 *     CREATE TABLE aura_auth_token (
 *         selector         VARCHAR(32)  NOT NULL PRIMARY KEY,
 *         hashed_validator VARCHAR(64)  NOT NULL,
 *         username         VARCHAR(255) NOT NULL,
 *         userdata         TEXT         NULL,
 *         label            VARCHAR(255) NULL,
 *         expires          INTEGER      NOT NULL,
 *         created_at       INTEGER      NOT NULL,
 *         last_used_at     INTEGER      NULL
 *     );
 *     CREATE INDEX aura_auth_token_username ON aura_auth_token (username);
 *
 * The `last_used_at` column is present whether or not last-use tracking is
 * enabled, so that turning tracking on later requires no migration.
 *
 * @package Aura.Auth
 *
 */
class PdoTokenStorage implements TokenStorageInterface
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
     * Whether to record the time a token was last presented.
     *
     * @var bool
     *
     */
    protected $track_last_used;

    /**
     *
     * Constructor.
     *
     * @param PDO $pdo A PDO connection.
     *
     * @param string $table The table holding the tokens.
     *
     * @param bool $track_last_used Whether to record the time a token was last
     * presented. Off by default: it costs an UPDATE on every authenticated
     * request, against the same row that request already reads. Turning it on
     * buys "last used" reporting and the ability to prune abandoned tokens.
     * Note that concurrent requests bearing the same token race on the update,
     * so the value is a rough indication rather than an audit record.
     *
     */
    public function __construct(
        PDO $pdo,
        $table = 'aura_auth_token',
        $track_last_used = false
    ) {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->track_last_used = (bool) $track_last_used;
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function create(
        $selector,
        $hashed_validator,
        $username,
        array $userdata,
        $expires,
        $created_at,
        $label = null
    ): void {
        $stm = "INSERT INTO {$this->table} "
             . "(selector, hashed_validator, username, userdata, label, expires, created_at) "
             . "VALUES (:selector, :hashed_validator, :username, :userdata, :label, :expires, :created_at)";
        $sth = $this->pdo->prepare($stm);
        $sth->execute(array(
            'selector' => $selector,
            'hashed_validator' => $hashed_validator,
            'username' => $username,
            'userdata' => json_encode($userdata),
            'label' => $label,
            'expires' => $expires,
            'created_at' => $created_at,
        ));
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function findBySelector($selector): ?array
    {
        $stm = "SELECT selector, hashed_validator, username, userdata, label, "
             . "expires, created_at, last_used_at "
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
        $row['created_at'] = (int) $row['created_at'];
        $row['last_used_at'] = ($row['last_used_at'] === null)
            ? null
            : (int) $row['last_used_at'];
        return $row;
    }

    /**
     *
     * {@inheritDoc}
     *
     * A no-op unless last-use tracking was enabled in the constructor.
     *
     */
    public function touch($selector, $now): void
    {
        if (! $this->track_last_used) {
            return;
        }

        $stm = "UPDATE {$this->table} SET last_used_at = :now "
             . "WHERE selector = :selector";
        $sth = $this->pdo->prepare($stm);
        $sth->execute(array(
            'now' => $now,
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
