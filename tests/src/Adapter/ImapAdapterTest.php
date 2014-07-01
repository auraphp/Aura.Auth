<?php
namespace Aura\Auth\Adapter;

use Aura\Auth\Phpfunc;

class ImapAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $adapter;

    protected function setUp()
    {
        $this->adapter = new ImapAdapter(
            new Phpfunc,
            '{mailbox.example.com:143/imap/secure}'
        );
    }

    public function testInstance()
    {
        $this->assertInstanceOf(
            'Aura\Auth\Adapter\ImapAdapter',
            $this->adapter
        );
    }
}
