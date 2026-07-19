<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Auth\Service;

use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\Auth;

/**
 *
 * Establishes authentication for a stateless request.
 *
 * This is the counterpart to {@see ResumeService}, minus everything that
 * assumes a session. ResumeService resumes the session first and gates the
 * adapter behind an idle/expiry {@see \Aura\Auth\Session\Timer}; neither makes
 * sense for a request that carries its own credential and ends. There is no
 * session to resume, and the credential's own expiry governs its lifetime, so
 * an idle timer would be measuring nothing.
 *
 * Give it an adapter that authenticates from the request itself — typically
 * {@see \Aura\Auth\Adapter\HeaderAdapter} — and an {@see Auth} backed by an
 * {@see \Aura\Auth\Session\ArraySegment}, so no session is touched at any point.
 *
 * @package Aura.Auth
 *
 */
class ApiResumeService
{
    /**
     *
     * An adapter that authenticates from the request.
     *
     * @var AdapterInterface
     *
     */
    protected $adapter;

    /**
     *
     * Constructor.
     *
     * @param AdapterInterface $adapter An adapter that authenticates from the
     * request, such as {@see \Aura\Auth\Adapter\HeaderAdapter}.
     *
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     *
     * Authenticates the current request, if it carries a usable credential.
     *
     * @param Auth $auth The authentication tracker to populate.
     *
     * @return bool True if the request was authenticated; false if it carried
     * no usable credential, leaving the tracker anonymous.
     *
     * @throws \Aura\Auth\Exception\TokenExpired when the request presents a
     * genuine but expired token, so the application can answer with something
     * more useful than a flat refusal.
     *
     */
    public function resume(Auth $auth): bool
    {
        $this->adapter->resume($auth);
        return ! $auth->isAnon();
    }
}
