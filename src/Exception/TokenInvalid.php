<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Auth\Exception;

use Aura\Auth\Exception;

/**
 *
 * Thrown when a presented API token is not usable.
 *
 * Raised alike for a malformed value, an unknown selector, and a validator
 * that does not match, so that the three cannot be told apart. Distinguishing
 * an unknown selector from a wrong validator would reveal whether a given
 * selector exists, and selectors travel in the clear as half of the token.
 *
 * @package Aura.Auth
 *
 */
class TokenInvalid extends Exception
{
}
