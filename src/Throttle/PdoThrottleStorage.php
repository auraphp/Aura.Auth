<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth\Throttle;

use PDO;

/**
 *
 * Stores failed-login counters in an SQL table via PDO, one row per failure.
 *
 * The window is a true sliding window: `getFailures()` counts only rows whose
 * `attempted_at` falls within the last `$window` seconds, and `deleteExpired()`
 * prunes rows older than that.
 *
 * Expected table (adjust types to your database):
 *
 *     CREATE TABLE aura_auth_throttle (
 *         id           INTEGER      NOT NULL PRIMARY KEY AUTOINCREMENT,
 *         throttle_key VARCHAR(255) NOT NULL,
 *         attempted_at INTEGER      NOT NULL
 *     );
 *     CREATE INDEX aura_auth_throttle_key ON aura_auth_throttle (throttle_key, attempted_at);
 *
 * (`throttle_key` avoids the reserved word `key`. On MySQL use
 * `AUTO_INCREMENT`; on PostgreSQL use a `SERIAL`/`BIGSERIAL` column.)
 *
 * @package Aura.Auth
 *
 */
class PdoThrottleStorage implements ThrottleStorageInterface
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
     * The table holding the failure rows.
     *
     * @var string
     *
     */
    protected $table;

    /**
     *
     * How long, in seconds, a failure is remembered.
     *
     * @var int
     *
     */
    protected $window;

    /**
     *
     * Constructor.
     *
     * @param PDO $pdo A PDO connection.
     *
     * @param string $table The table holding the failure rows.
     *
     * @param int $window How long a failure is remembered, in seconds (default
     * 15 minutes).
     *
     */
    public function __construct(PDO $pdo, $table = 'aura_auth_throttle', $window = 900)
    {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->window = (int) $window;
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function recordFailure(string $key): void
    {
        $stm = "INSERT INTO {$this->table} (throttle_key, attempted_at) "
             . "VALUES (:key, :now)";
        $sth = $this->pdo->prepare($stm);
        $sth->execute(array('key' => $key, 'now' => time()));
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function getFailures(string $key): array
    {
        $stm = "SELECT COUNT(*) AS failures, MAX(attempted_at) AS last_at "
             . "FROM {$this->table} "
             . "WHERE throttle_key = :key AND attempted_at >= :since";
        $sth = $this->pdo->prepare($stm);
        $sth->execute(array('key' => $key, 'since' => time() - $this->window));
        $row = $sth->fetch(PDO::FETCH_ASSOC);

        $count = (int) $row['failures'];
        return array(
            'count' => $count,
            'last' => ($count > 0 && $row['last_at'] !== null)
                ? (int) $row['last_at']
                : null,
        );
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function reset(string $key): void
    {
        $stm = "DELETE FROM {$this->table} WHERE throttle_key = :key";
        $sth = $this->pdo->prepare($stm);
        $sth->execute(array('key' => $key));
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function deleteExpired(): void
    {
        $stm = "DELETE FROM {$this->table} WHERE attempted_at < :since";
        $sth = $this->pdo->prepare($stm);
        $sth->execute(array('since' => time() - $this->window));
    }
}
