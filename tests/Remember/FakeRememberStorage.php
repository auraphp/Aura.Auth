<?php
namespace Aura\Auth\Remember;

/**
 * An in-memory RememberStorageInterface implementation for tests.
 */
class FakeRememberStorage implements RememberStorageInterface
{
    public $rows = array();

    public function create($selector, $hashed_validator, $username, array $userdata, $expires)
    {
        $this->rows[$selector] = array(
            'selector' => $selector,
            'hashed_validator' => $hashed_validator,
            'username' => $username,
            'userdata' => $userdata,
            'expires' => (int) $expires,
        );
    }

    public function findBySelector($selector)
    {
        return isset($this->rows[$selector]) ? $this->rows[$selector] : null;
    }

    public function update($selector, $hashed_validator, $expires)
    {
        if (isset($this->rows[$selector])) {
            $this->rows[$selector]['hashed_validator'] = $hashed_validator;
            $this->rows[$selector]['expires'] = (int) $expires;
        }
    }

    public function deleteBySelector($selector)
    {
        unset($this->rows[$selector]);
    }

    public function deleteByUsername($username)
    {
        foreach ($this->rows as $selector => $row) {
            if ($row['username'] === $username) {
                unset($this->rows[$selector]);
            }
        }
    }

    public function deleteExpired()
    {
        foreach ($this->rows as $selector => $row) {
            if ($row['expires'] < time()) {
                unset($this->rows[$selector]);
            }
        }
    }
}
