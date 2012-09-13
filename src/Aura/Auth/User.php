<?php
/**
 * 
 * This file is part of the Aura project for PHP.
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Auth;

/**
 * 
 * A class representing a single authenticated user.
 * 
 * @package Aura.Auth
 * 
 */
class User
{
    /**
     * 
     * @var string
     * 
     */
    protected $username  = null;

    /**
     * 
     * @var string
     * 
     */
    protected $full_name = null;

    /**
     * 
     * @var string
     * 
     */
    protected $email     = null;

    /**
     * 
     * @var string
     * 
     */
    protected $uri       = null;

    /**
     * 
     * @var string
     * 
     */
    protected $avatar    = null;

    /**
     * 
     * @var string
     * 
     */
    protected $unique_id = null;


    /**
     *
     * Magic __get.
     *
     * @param string $key
     *
     * @return mixed
     *
     */
    public function __get($key)
    {
        return $this->$key;
    }

    /**
     *
     * Magic __clone, reset the properties.
     *
     */
    public function __clone()
    {
        $this->username  = null;
        $this->full_name = null;
        $this->email     = null;
        $this->uri       = null;
        $this->avatar    = null;
        $this->unique_id = null;
    }

    /**
     *
     * Magic __sleep, return a list of properties to be serialized.
     *
     * @return array
     *
     */
    public function __sleep()
    {
        return ['username', 'full_name', 'email', 'uri', 'avatar', 'unique_id'];
    }

    /**
     *
     * Populate this object with values from an array.
     *
     * @param array $set
     * 
     * @throws Aura\Auth\Exception If the username property was not set.
     *
     */
    public function setFromArray(array $set)
    {
        $valid = ['username', 'full_name', 'email',
                  'uri',      'avatar',    'unique_id'];

        foreach ($set as $key => $value) {
            if (in_array($key, $valid)) {
                $this->$key = $value;
            }
            // unknown / unwanted key, just ignore
        }

        if (! $this->username) {
            throw new Exception('Username is required');
        }
    }
}

