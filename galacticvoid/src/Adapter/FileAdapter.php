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

use Aura\Auth\Exception\FileDoesNotExists;
use Aura\Auth\Exception\FileNotReadable;

abstract class FileAdapter implements AuthInterface
{
    /**
     * 
     * @var string Path to ini file.
     * 
     */
    protected $file;

    /**
     * 
     * @var string Username
     * 
     */
    protected $username;
    
    /**
     * 
     * @var string Password
     * 
     */
    protected $password;


    /**
     * 
     * @param string $file The ini file to use for Auth.
     * 
     * @param string $username
     * 
     * @param string $password
     * 
     * @throws Aura\Auth\Exception If $file does not exist.
     * 
     */
    public function __construct($file, $username = null, $password = null)
    {        
        $this->file = realpath($file);
        $this->setUsername($username);
        $this->setPassword($password);

        // does the file exist?
        if (! file_exists($this->file)) {
            throw new FileDoesNotExists();
        }
        if (! is_readable($this->file)) {
            throw new FileNotReadable();
        }
    }
    
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }
    
    public function getUsername($username)
    {
        return $this->username;
    }
    
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }
    
    public function getPassword($password)
    {
        return $this->password;
    }
}
