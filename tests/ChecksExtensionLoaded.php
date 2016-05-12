<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth;

/**
 *
 * Trait for test classes which do extension checking.
 *
 * @package Aura.Auth
 *
 */
trait ChecksExtensionLoaded
{
    /**
     *
     * Mark a test skipped unless a specified extension is loaded.
     *
     * @param string $extension The extension to check.
     *
     */
    public function skipUnlessExtensionLoaded($extension) {
        if (false === extension_loaded($extension)) {
            $this->markTestSkipped("Cannot run this test without $extension loaded.");
        }
    }
}
