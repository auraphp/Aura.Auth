<?php
namespace Aura\Auth\Adapter;

class NullAdapter extends AbstractAdapter
{
    public function login(array $cred)
    {
        return array(null, null);
    }
}
