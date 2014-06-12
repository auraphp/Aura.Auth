<?php
namespace Aura\Auth\Session;

class SessionArrayTest extends AbstractSessionTest
{
    protected function setUp()
    {
        $this->data = array();
        $this->session = new SessionArray($this->data);
    }

    protected function getData($key)
    {
        return $this->data[$key];
    }
}
