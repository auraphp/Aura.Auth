<?php
namespace Aura\Auth;

/**
 * A Phpfunc test double that records setcookie() calls while delegating
 * everything else (random_bytes, etc.) to the real PHP functions.
 */
class FakePhpfunc extends Phpfunc
{
    public $cookies = array();

    public function setcookie($name, $value = '', $options = array())
    {
        $this->cookies[$name] = array(
            'value' => $value,
            'options' => $options,
        );
        return true;
    }
}
