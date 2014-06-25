<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @package Aura.Auth
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth\Session;

/**
 *
 * A $this->data segment; it attaches to $this->data lazily (i.e., only after a
 * session becomes available.)
 *
 * @package Aura.Auth
 *
 */
class NullSegment implements SegmentInterface
{
    protected $data = array();

    /**
     *
     * @param mixed $key
     *
     * @param mixed $alt
     *
     * @return mixed
     *
     */
    public function get($key, $alt = null)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        return $alt;
    }

    /**
     *
     * @param mixed $key
     *
     * @param mixed $val
     *
     * @return mixed
     *
     */
    public function set($key, $val)
    {
        if (! isset($this->data)) {
            return;
        }

        $this->data[$key] = $val;
    }
}
