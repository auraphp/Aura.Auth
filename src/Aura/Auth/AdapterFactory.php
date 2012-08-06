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
 * 
 * 
 * @package Aura.Auth
 * 
 */
class AdapterFactory
{
    protected $adapters;


    public function __construct(array $adapters)
    {
        $this->adapters = $adapters;
    }

    public function newInstance($name)
    {
        if (empty($this->adapters[$name])) {
            throw new Exception("Adapter `{$name}` was not found.");
        }

        if ($this->adapters[$name] instanceOf \Closure) {
            $this->adapters[$name] = $this->adapters[$name]();
        }

        return $this->adapters[$name];
    }
}