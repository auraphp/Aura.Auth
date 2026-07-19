<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth;

use Aura\Session_Interface\SegmentInterface;
use Aura\Auth\Session\Timer;

/**
 *
 * The current user (authenticated or otherwise).
 *
 * @package Aura.Auth
 *
 */
class Auth
{
    /**
     *
     * Session data.
     *
     * @var SegmentInterface
     *
     */
    protected $segment;

    /**
     *
     * Constructor.
     *
     * @param SegmentInterface $segment A session data store.
     *
     */
    public function __construct(SegmentInterface $segment)
    {
        $this->segment = $segment;
    }

    /**
     *
     * Sets the authentication values.
     *
     * @param string $status The authentication status.
     *
     * @param int $first_active First active at this Unix time.
     *
     * @param int $last_active Last active at this Unix time.
     *
     * @param string $username The username.
     *
     * @param array $userdata Arbitrary user data.
     *
     * @return void
     *
     * @see Status for constants and their values.
     *
     */
    public function set(
        $status,
        $first_active,
        $last_active,
        $username,
        array $userdata
    ): void {
        $this->setStatus($status);
        $this->setFirstActive($first_active);
        $this->setLastActive($last_active);
        $this->setUserName($username);
        $this->setUserData($userdata);
    }

    /**
     *
     * Is the user authenticated?
     *
     * @return bool
     *
     */
    public function isValid(): bool
    {
        return $this->getStatus() == Status::VALID;
    }

    /**
     *
     * Is the user anonymous?
     *
     * @return bool
     *
     */
    public function isAnon(): bool
    {
        return $this->getStatus() == Status::ANON;
    }

    /**
     *
     * Has the user been idle for too long?
     *
     * @return bool
     *
     */
    public function isIdle(): bool
    {
        return $this->getStatus() == Status::IDLE;
    }

    /**
     *
     * Has the authentication time expired?
     *
     * @return bool
     *
     */
    public function isExpired(): bool
    {
        return $this->getStatus() == Status::EXPIRED;
    }

    /**
     *
     * Was the user re-authenticated from a "remember me" token (and did not
     * pass credentials this session)? Such a user is authenticated but should
     * be treated as lower privilege than a VALID user.
     *
     * @return bool
     *
     */
    public function isRemembered(): bool
    {
        return $this->getStatus() == Status::REMEMBERED;
    }

    /**
     *
     * Sets the current authentication status.
     *
     * @param string $status The authentication status.
     *
     * @return void
     *
     */
    public function setStatus($status): void
    {
        $this->segment->set('status', $status);
    }

    /**
     *
     * Gets the current authentication status.
     *
     * @return string
     *
     */
    public function getStatus(): string
    {
        return $this->segment->get('status', Status::ANON);
    }

    /**
     *
     * Sets the initial authentication time.
     *
     * @param int $first_active The initial authentication Unix time.
     *
     * @return void
     *
     */
    public function setFirstActive($first_active): void
    {
        $this->segment->set('first_active', $first_active);
    }

    /**
     *
     * Gets the initial authentication time.
     *
     * @return ?int
     *
     */
    public function getFirstActive(): ?int
    {
        return $this->segment->get('first_active');
    }

    /**
     *
     * Sets the last active time.
     *
     * @param int $last_active The last active Unix time.
     *
     * @return void
     *
     */
    public function setLastActive($last_active): void
    {
        $this->segment->set('last_active', $last_active);
    }

    /**
     *
     * Gets the last active time.
     *
     * @return ?int
     *
     */
    public function getLastActive(): ?int
    {
        return $this->segment->get('last_active');
    }

    /**
     *
     * Sets the current user name.
     *
     * @param string $username The username.
     *
     * @return void
     *
     */
    public function setUserName($username): void
    {
        $this->segment->set('username', $username);
    }

    /**
     *
     * Gets the current user name.
     *
     * @return ?string
     *
     */
    public function getUserName(): ?string
    {
        return $this->segment->get('username');
    }

    /**
     *
     * Sets the current user data.
     *
     * @param array $userdata The user data.
     *
     * @return void
     *
     */
    public function setUserData(array $userdata): void
    {
        $this->segment->set('userdata', $userdata);
    }

    /**
     *
     * Gets the current user data.
     *
     * @return array
     *
     */
    public function getUserData(): array
    {
        return $this->segment->get('userdata', array());
    }
}
