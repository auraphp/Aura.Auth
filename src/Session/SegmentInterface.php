<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth\Session;

use Aura\Session_Interface\SegmentInterface as BaseSegmentInterface;

/**
 *
 * Interface for segment of the $_SESSION array.
 *
 * @deprecated Type-hint against {@see \Aura\Session_Interface\SegmentInterface}
 * instead. This interface is retained for backward compatibility and simply
 * re-exports the shared contract.
 *
 * @package Aura.Auth
 *
 */
interface SegmentInterface extends BaseSegmentInterface
{
}
