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

/**
 *
 * Abstract Authentication Storage.
 *
 * @package Aura.Auth
 *
 */
interface AdapterInterface
{
    public function login($cred);

    public function logout($user, array $info = array());

    public function getUser();

    public function getInfo();

    public function getError();
}
