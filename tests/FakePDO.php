<?php
namespace Aura\Auth;

use PDO;

class FakePdo extends PDO
{
    public function __construct()
    {
        // do nothing
    }
}
