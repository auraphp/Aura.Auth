<?php
namespace Aura\Auth\Adapter;

use Aura\Auth\Auth;
use Aura\Auth\Exception;

class FakeAdapter extends AbstractAdapter
{
    protected $accounts = array();

    public ?array $input = null;

    public function __construct(array $accounts = array())
    {
        $this->accounts = $accounts;
    }

    public function login(array $input): array
    {
        $this->input = $input;
        return array($input['username'], array());
    }
}
