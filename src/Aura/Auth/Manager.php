<?php
/**
 * 
 * This file is part of the Aura project for PHP.
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Auth;

use Aura\Auth\Adapter\AuthInterface;

/**
 * 
 * 
 * 
 * @package Aura.Auth
 * 
 */
class Manager
{
    /**
     * 
     * A list of available Auth adapters.
     * 
     * Format: [adapter_name => adapter]
     * 
     * @var array 
     * 
     */
    protected $adapters = [];
    
    /**
     * 
     * @param array $adapters List of available Auth adapters. Format:
     * adapter_name => function () { return new Adapter(...); },
     * 
     */
    public function __construct(array $adapters = [])
    {
        $this->adapters = $adapters;
    }

    /**
     * 
     * Set an Auth adapter.
     *
     * @param string $name
     * 
     * @param Aura\Auth\Adapter\AuthInterface $adapter
     *
     */
    public function setAdapter($name, AuthInterface $adapter)
    {
        $this->adapters[$name] = $adapter;
    }

    /**
     * 
     * Authenticate a user using `$adapter`.
     * 
     * @param string $adapter Adapter name.
     * 
     * @throws Aura\Auth\Exception If the adapter was not found.
     * 
     * @return boolean
     * 
     */
    public function authenticate($adapter_name, array $opts = [])
    {
        if (! isset($this->adapters[$adapter_name])) {
            $adapter = htmlspecialchars($adapter_name);
            throw new Exception("Adapter `{$adapter_name}` was not found.");
        }

        if ($this->adapters[$adapter_name] instanceOf \Closure) {
            $this->adapters[$adapter_name] = $this->adapters[$adapter_name]();
        }

        return $this->adapters[$adapter_name]->authenticate($opts);
    }
}