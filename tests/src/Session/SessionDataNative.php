<?php
namespace Aura\Auth\Session;

/**
 * @runTestsInSeparateProcesses
 */
class SessionDataNativeTest extends AbstractSessionDataTest
{
    protected function setUp()
    {
        session_start();
        $this->data = new SessionDataNative(__CLASS__);
    }

    protected function getData($key)
    {
        return $_SESSION[__CLASS__][$key];
    }
}
