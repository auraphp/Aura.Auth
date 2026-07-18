<?php
namespace Aura\Auth\Adapter;

use Aura\Auth\Phpfunc;

/**
 * Declares the LDAP functions proxied by Phpfunc as real methods so that
 * tests can mock them with the (non-deprecated) MockBuilder::onlyMethods().
 */
class FakeLdapPhpfunc extends Phpfunc
{
    public function ldap_connect(...$args)
    {
    }

    public function ldap_bind(...$args)
    {
    }

    public function ldap_unbind(...$args)
    {
    }

    public function ldap_search(...$args)
    {
    }

    public function ldap_get_entries(...$args)
    {
    }

    public function ldap_set_option(...$args)
    {
    }

    public function ldap_errno(...$args)
    {
    }

    public function ldap_error(...$args)
    {
    }
}
