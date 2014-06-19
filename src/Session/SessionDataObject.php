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
namespace Aura\Auth\Session;

/**
 *
 * Session Data Object
 *
 * @package Aura.Auth
 *
 */
class SessionDataObject implements SessionDataInterface
{
    /**
     *
     * @var mixed
     *
     */
    protected $object;

    /**
     *
     * @param mixed $object
     *
     */
    public function __construct($object)
    {
        $this->object = $object;
    }

    /**
     *
     * @param mixed $key
     *
     * @return mixed
     *
     */
    public function __get($key)
    {
        if (isset($this->object->$key)) {
            return $this->object->$key;
        }
    }

    /**
     *
     * @param mixed $key
     *
     * @param mixed $val
     *
     * @return void
     *
     */
    public function __set($key, $val)
    {
        $this->object->$key = $val;
    }

    /**
     *
     * @param mixed $key
     *
     * @return bool
     *
     */
    public function __isset($key)
    {
        return isset($this->object->$key);
    }

    /**
     *
     * @param mixed $key
     *
     * @return void
     *
     */
    public function __unset($key)
    {
        unset($this->object->$key);
    }
}
