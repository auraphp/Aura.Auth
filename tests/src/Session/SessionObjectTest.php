<?php
namespace Aura\Auth\Session;

class SessionObjectTest extends AbstractSessionTest
{
    protected function setUp()
    {
        $this->data = (object) array();
        $this->session = new SessionObject($this->data);
    }

    protected function getData($key)
    {
        return $this->data->$key;
    }
}
