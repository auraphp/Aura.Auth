<?php
namespace Aura\Auth\Session;

class SessionDataObjectTest extends AbstractSessionDataTest
{
    protected $object;

    public function test()
    {
        $this->object = (object) array();
        $this->data = new SessionDataObject($this->object);
        parent::test();
    }

    protected function getData($key)
    {
        return $this->object->$key;
    }
}
