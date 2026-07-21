<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Auth\Rehash;

use PDO;

/**
 *
 * Replaces a stored password hash in an SQL table via PDO.
 *
 * This covers the common case of a single accounts table with a username
 * column and a password column. Anything less direct -- a password held in a
 * joined table, a composite key, an audit trail to write alongside the update
 * -- wants its own implementation of {@see RehashStorageInterface}, which is a
 * single method.
 *
 * @package Aura.Auth
 *
 */
class PdoRehashStorage implements RehashStorageInterface
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
     * The table holding the accounts.
     *
     * @var string
     *
     */
    protected $table;

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
     * The password column to overwrite.
     *
     * @var string
     *
     */
    protected $password_col;

    /**
     *
     * The algorithm to hash with.
     *
     * @var string|int|null
     *
     */
    protected $algo;

    /**
     *
     * Options for the algorithm.
     *
     * @var array
     *
     */
    protected $options;

    /**
     *
     * Constructor.
     *
     * Note that the table and column names are interpolated into the SQL, as
     * identifiers cannot be bound; pass configured values, never user input.
     * This matches how PdoAdapter takes its own columns and FROM clause.
     *
     * @param PDO $pdo A PDO connection. It must be writable -- a read replica
     * will fail every rehash.
     *
     * @param string $table The table holding the accounts.
     *
     * @param string $username_col The username column.
     *
     * @param string $password_col The password column to overwrite.
     *
     * @param string|int $algo The algorithm to hash with; this is the
     * algorithm being migrated *to*, so it is normally left at bcrypt even
     * when the adapter verifies something older.
     *
     * @param array $options Options for the algorithm, as for password_hash().
     * Pass the same ones given to the verifier, or the rehash will produce a
     * hash the verifier immediately reports as needing rehashing again.
     *
     */
    public function __construct(
        PDO $pdo,
        $table = 'accounts',
        $username_col = 'username',
        $password_col = 'password',
        $algo = PASSWORD_BCRYPT,
        array $options = array()
    ) {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->username_col = $username_col;
        $this->password_col = $password_col;
        $this->algo = $algo;
        $this->options = $options;
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function rehash($username, $plaintext): void
    {
        $stm = "UPDATE {$this->table} "
             . "SET {$this->password_col} = :password "
             . "WHERE {$this->username_col} = :username";
        $sth = $this->pdo->prepare($stm);
        $sth->execute(array(
            'password' => password_hash($plaintext, $this->algo, $this->options),
            'username' => $username,
        ));
    }
}
