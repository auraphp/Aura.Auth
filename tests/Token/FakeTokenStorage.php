<?php
namespace Aura\Auth\Token;

/**
 *
 * An in-memory TokenStorageInterface for tests.
 *
 */
class FakeTokenStorage implements TokenStorageInterface
{
    public $rows = array();

    /** Selectors passed to touch(), so tests can assert on redundant writes. */
    public $touched = array();

    public function create(
        $selector,
        $hashed_validator,
        $username,
        array $userdata,
        $expires,
        $created_at,
        $label = null
    ): void {
        $this->rows[$selector] = array(
            'selector' => $selector,
            'hashed_validator' => $hashed_validator,
            'username' => $username,
            'userdata' => $userdata,
            'label' => $label,
            'expires' => (int) $expires,
            'created_at' => (int) $created_at,
            'last_used_at' => null,
        );
    }

    public function findBySelector($selector): ?array
    {
        return isset($this->rows[$selector])
            ? $this->rows[$selector]
            : null;
    }

    public function touch($selector, $now): void
    {
        $this->touched[] = $selector;

        if (isset($this->rows[$selector])) {
            $this->rows[$selector]['last_used_at'] = (int) $now;
        }
    }

    public function deleteBySelector($selector): void
    {
        unset($this->rows[$selector]);
    }

    public function deleteByUsername($username): void
    {
        foreach ($this->rows as $selector => $row) {
            if ($row['username'] === $username) {
                unset($this->rows[$selector]);
            }
        }
    }

    public function deleteExpired(): void
    {
        $now = time();
        foreach ($this->rows as $selector => $row) {
            if ($row['expires'] < $now) {
                unset($this->rows[$selector]);
            }
        }
    }
}
