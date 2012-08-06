<?php
/**
 * 
 * This file is part of the Aura project for PHP.
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Auth\Adapter;

use Aura\Auth\Exception;
use Aura\Auth\User;


class MockAdapter implements AuthInterface
{
    public function authenticate(array $opts = [])
    {
        if (isset($GLOBALS['Aura\Auth']['missing_option'])) {
            $msg = 'The option `username` and / or `password` is missing.';
            throw new Exception($msg);
        }

        if (isset($GLOBALS['Aura\Auth']['wrong'])) {
            return false;
        }

        $array = [
            'username'  => 'jdoe',
            'full_name' => 'john doe',
            'email'     => 'jdoe@example.com', 
            'uri'       => 'example.com',
            'avatar'    => 'example.com/avatar.jpg',
            'unique_id' => 'jdoe'
        ];
        
        $user = new User;
        $user->setFromArray($array);

        return $user;
    }

}