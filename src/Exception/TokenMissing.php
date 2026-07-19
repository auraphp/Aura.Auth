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
 * Thrown when no API token was presented at all.
 *
 * Distinct from {@see TokenInvalid}: this says the request carried no
 * credential, not that it carried a bad one, which lets an application answer
 * with a challenge rather than a rejection.
 *
 * @package Aura.Auth
 *
 */
class TokenMissing extends Exception
{
}
