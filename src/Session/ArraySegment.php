<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Auth\Session;

/**
 *
 * A segment held entirely in memory, for authentication that must not touch
 * $_SESSION — API requests authenticated from a header, for instance.
 *
 * {@see Segment} silently discards writes when no session is active, which is
 * correct for its purpose but fatal here: {@see \Aura\Auth\Auth} keeps no state
 * of its own and reads every value back out of its segment, so a discarded
 * write means an authenticated user reads back as anonymous within the very
 * same request. This implementation simply keeps the values, so the handoff
 * works with no session involved.
 *
 * Nothing is persisted: the values live for one request and are gone. That is
 * the intent — a stateless request re-authenticates from the credential it was
 * given rather than restoring anything.
 *
 * @package Aura.Auth
 *
 */
class ArraySegment implements SegmentInterface
{
    /**
     *
     * The segment values.
     *
     * @var array
     *
     */
    protected $data = array();

    /**
     *
     * Constructor.
     *
     * @param array $data Initial segment values.
     *
     */
    public function __construct(array $data = array())
    {
        $this->data = $data;
    }

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
        if (array_key_exists($key, $this->data)) {
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
