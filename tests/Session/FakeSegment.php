<?php
namespace Aura\Auth\Session;

class FakeSegment implements SegmentInterface
{
    protected $data = array();

    public function get(string $key, mixed $alt = null): mixed
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        return $alt;
    }

    public function set(string $key, mixed $val): void
    {
        $this->data[$key] = $val;
    }
}
