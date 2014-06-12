<?php
namespace Aura\Auth\Session;

abstract class AbstractSession implements SessionInterface
{
    protected $data;

    protected $regenid;

    public function __construct(
        &$data,
        $regenid = null
    ) {
        $this->data = &$data;
        $this->regenid = $regenid;
    }

    abstract public function __get($key);

    abstract public function __set($key, $val);

    abstract public function __isset($key);

    abstract public function __unset($key);

    public function regenerateId()
    {
        if ($this->regenid) {
            return call_user_func($this->regenid);
        }

        return session_regenerate_id(true);
    }
}
