<?php
/**
 *
 * This file is part of Aura for PHP.
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
    /**
     *
     * The segment data.
     *
     * @var array
     *
     */
    protected $data = array();

    /**
     *
     * Gets a value from the segment.
     *
     * @param string $key A key for the segment value.
     *
     * @param mixed $alt Return this value if the segment key does not exist.
     *
     * @return mixed
     *
     */
    public function get(string $key, mixed $alt = null): mixed
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        return $alt;
    }

    /**
     *
     * Sets a value in the segment.
     *
     * @param string $key The key in the segment.
     *
     * @param mixed $val The value to set.
     *
     */
    public function set(string $key, mixed $val): void
    {
        $this->data[$key] = $val;
    }
}
