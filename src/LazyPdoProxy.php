<?php
/**
 *
 * This file is part of the Aura project for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth;

use PDO;

/**
 *
 * A proxy to a PDO instance that only gets created when it is first called.
 *
 * @package Aura.Auth
 *
 */
class LazyPdoProxy extends Pdo
{
    /**
     *
     * The DSN for a lazy connection.
     *
     * @var string
     *
     */
    private $dsn;

    /**
     *
     * PDO options for a lazy connection.
     *
     * @var array
     *
     */
    private $options = array();

    /**
     *
     * The password for a lazy connection.
     *
     * @var string
     *
     */
    private $password;

    /**
     *
     * The instance of PDO being proxied.
     *
     * @var PDO
     *
     */
    private $pdo;

    /**
     *
     * The username for a lazy connection.
     *
     * @var string
     *
     */
    private $username;

    /**
     *
     * You may pass a normal set of PDO constructor parameters, and LazyPdo will
     * use them for a lazy connection.
     *
     * @param string $dsn The data source name for a lazy PDO connection.
     *
     * @param string $username The username for a lazy connection.
     *
     * @param string $password The password for a lazy connection.
     *
     * @param array $options Driver-specific options for a lazy connection.
     *
     * @see http://php.net/manual/en/pdo.construct.php
     *
     */
    public function __construct(
        $dsn,
        $username = null,
        $password = null,
        array $options = array()
    ) {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;
    }

    /**
     *
     * Calls a method on the proxied PDO object, creating the PDO connection if
     * needed.
     *
     * @param string $method The method to call.
     *
     * @param array $params The params to pass to the method.
     *
     * @return mixed
     *
     * @see http://php.net/pdo
     *
     */
    public function __call($method, array $params = array())
    {
        return call_user_func_array(array($this->getPdo(), $method), $params);
    }

    /**
     *
     * Gets a property on the proxied PDO object, creating the PDO connection if
     * needed.
     *
     * @param string $key The property name.
     *
     * @return mixed
     *
     * @see http://php.net/pdo
     *
     */
    public function __get($key)
    {
        return $this->getPdo()->$key;
    }

    /**
     *
     * Returns the proxied PDO instance, creating it if needed.
     *
     * @return PDO
     *
     */
    private function getPdo()
    {
        if (! $this->pdo) {
            $this->pdo = new PDO(
                $this->dsn,
                $this->username,
                $this->password,
                $this->options
            );
        }

        return $this->pdo;
    }
}
