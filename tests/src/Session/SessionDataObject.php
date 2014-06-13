<?php
namespace Aura\Auth\Session;

class SessionDataObjectTest extends AbstractSessionDataTest
{
    protected $object;

    protected function setUp()
    {
        $this->object = (object) array();
        $this->data = new SessionDataObject($this->object);
    }

    protected function getData($key)
    {
        return $this->object->$key;
    }
}
