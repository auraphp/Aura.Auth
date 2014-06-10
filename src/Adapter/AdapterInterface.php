<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @package Aura.Autoload
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth;

/**
 *
 * Abstract Authentication Storage.
 *
 * @package Aura.Auth
 *
 */
interface StorageInterface
{
    public function login($credentials);

    public function logout($user, array $info = array());

    public function getUser();

    public function getInfo();

    public function getError();
}
