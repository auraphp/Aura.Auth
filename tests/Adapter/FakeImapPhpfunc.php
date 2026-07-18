<?php
namespace Aura\Auth\Adapter;

use Aura\Auth\Phpfunc;

/**
 * Declares the IMAP functions proxied by Phpfunc as real methods so that
 * tests can mock them with the (non-deprecated) MockBuilder::onlyMethods().
 */
class FakeImapPhpfunc extends Phpfunc
{
    public function imap_open(...$args)
    {
    }

    public function imap_close(...$args)
    {
    }
}
