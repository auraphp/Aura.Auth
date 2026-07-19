<?php
namespace Aura\Auth\Throttle;

/**
 * An in-memory ThrottleStorageInterface implementation for tests.
 */
class FakeThrottleStorage implements ThrottleStorageInterface
{
    public $window;

    public $failures = array();

    public function __construct($window = 900)
    {
        $this->window = (int) $window;
    }

    public function recordFailure(string $key): void
    {
        $this->failures[$key][] = time();
    }

    public function getFailures(string $key): array
    {
        $since = time() - $this->window;
        $recent = array_filter(
            isset($this->failures[$key]) ? $this->failures[$key] : array(),
            function ($at) use ($since) {
                return $at >= $since;
            }
        );

        return array(
            'count' => count($recent),
            'last' => $recent ? max($recent) : null,
        );
    }

    public function reset(string $key): void
    {
        unset($this->failures[$key]);
    }

    public function deleteExpired(): void
    {
        $since = time() - $this->window;
        foreach ($this->failures as $key => $times) {
            $this->failures[$key] = array_values(array_filter(
                $times,
                function ($at) use ($since) {
                    return $at >= $since;
                }
            ));
            if (! $this->failures[$key]) {
                unset($this->failures[$key]);
            }
        }
    }
}
