<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth\Session;

use Aura\Session_Interface\SessionInterface as BaseSessionInterface;

/**
 *
 * Interface for a session manager.
 *
 * @deprecated Type-hint against {@see \Aura\Session_Interface\SessionInterface}
 * instead. This interface is retained for backward compatibility and simply
 * re-exports the shared contract.
 *
 * @package Aura.Auth
 *
 */
interface SessionInterface extends BaseSessionInterface
{
}
