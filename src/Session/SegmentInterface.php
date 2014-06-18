<?php
namespace Aura\Auth\Session;

interface SegmentInterface
{
    public function get($key, $alt = null);
    public function set($key, $val);
}
