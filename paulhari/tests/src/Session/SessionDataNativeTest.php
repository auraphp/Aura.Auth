<?php
namespace Aura\Auth\Session;

/**
 * @runTestsInSeparateProcesses
 */
class SessionDataNativeTest extends AbstractSessionDataTest
{
    public function test()
    {
        // create the data object before the session starts
        $this->data = new SessionDataNative(__CLASS__);

        // before the session starts
        $this->data->foo = 'bar';
        $this->assertNull($this->data->foo);

        // after the session starts
        session_start();
        parent::test();
    }

    protected function getData($key)
    {
        return $_SESSION[__CLASS__][$key];
    }
}
