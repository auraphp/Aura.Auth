<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Auth\Adapter;

/**
 *
 * NullAdapter
 *
 * @package Aura.Auth
 *
 */
class NullAdapter extends AbstractAdapter
{
    /**
     *
     * login
     *
     * @param array $input
     *
     * @return array
     *
     */
    public function login(#[\SensitiveParameter] array $input): array
    {
        return array(null, null);
    }
}
