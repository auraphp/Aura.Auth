<?php
namespace Aura\Auth\Adapter;

use Aura\Auth\Phpfunc;

class ImapAdapterTest extends \PHPUnit\Framework\TestCase
{
    protected $phpfunc;

    protected $adapter;

    protected function setUp() : void
    {
        $this->phpfunc = $this->getMockBuilder(FakeImapPhpfunc::class)
             ->onlyMethods(array(
                 'imap_open',
                 'imap_close',
             ))
             ->getMock();

        $this->adapter = new ImapAdapter(
            $this->phpfunc,
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

    public function testLogin()
    {
        $this->phpfunc->expects($this->once())
            ->method('imap_open')
            ->with(
                '{mailbox.example.com:143/imap/secure}',
                'someusername',
                'secretpassword',
                0,
                1,
                null
            )
            ->willReturn(true);

        $actual = $this->adapter->login(array(
            'username' => 'someusername',
            'password' => 'secretpassword'
        ));

        $expect = array('someusername', array());

        $this->assertSame($expect, $actual);
    }

    public function testLogin_connectionFailed()
    {
        $this->phpfunc->expects($this->once())
            ->method('imap_open')
            ->with('{mailbox.example.com:143/imap/secure}')
            ->willReturn(false);

        $this->expectException('Aura\Auth\Exception\ConnectionFailed');
        $this->adapter->login(array(
            'username' => 'someusername',
            'password' => 'secretpassword'
        ));
    }
}
