<?php
namespace Aura\Auth\Token;

use Aura\Auth\Exception\TokenExpired;
use Aura\Auth\Phpfunc;

class TokenServiceTest extends \PHPUnit\Framework\TestCase
{
    protected $storage;

    protected $service;

    protected function setUp() : void
    {
        $this->storage = new FakeTokenStorage;
        $this->service = new TokenService(
            $this->storage,
            new SplitToken(new Phpfunc)
        );
    }

    public function testIssueReturnsParseableValueAndStoresOnlyTheHash()
    {
        $value = $this->service->issue('boshag');

        $parsed = (new SplitToken(new Phpfunc))->parseValue($value);
        $this->assertNotNull($parsed);

        $row = $this->storage->findBySelector($parsed['selector']);
        $this->assertSame('boshag', $row['username']);

        // the raw validator must not be recoverable from storage
        $this->assertNotSame($parsed['validator'], $row['hashed_validator']);
        $this->assertSame(
            hash('sha256', $parsed['validator']),
            $row['hashed_validator']
        );
    }

    public function testIssueStoresUserdataAndLabel()
    {
        $value = $this->service->issue(
            'boshag',
            array('scopes' => array('read:builds')),
            'CI deploy'
        );

        $row = $this->service->verify($value);
        $this->assertSame(array('scopes' => array('read:builds')), $row['userdata']);
        $this->assertSame('CI deploy', $row['label']);
    }

    public function testIssueUsesDefaultTtl()
    {
        $before = time();
        $value = $this->service->issue('boshag');
        $row = $this->service->verify($value);

        $this->assertGreaterThanOrEqual($before + 2592000, $row['expires']);
        $this->assertLessThanOrEqual(time() + 2592000, $row['expires']);
    }

    public function testIssueTtlOverride()
    {
        $before = time();
        $value = $this->service->issue('boshag', array(), null, 60);
        $row = $this->service->verify($value);

        $this->assertGreaterThanOrEqual($before + 60, $row['expires']);
        $this->assertLessThanOrEqual(time() + 60, $row['expires']);
    }

    public function testIssuedValuesAreUnique()
    {
        $this->assertNotSame(
            $this->service->issue('boshag'),
            $this->service->issue('boshag')
        );
    }

    public function testVerifyReturnsTheRow()
    {
        $value = $this->service->issue('boshag');
        $row = $this->service->verify($value);

        $this->assertSame('boshag', $row['username']);
    }

    public function testVerifyRejectsMalformedValue()
    {
        $this->assertNull($this->service->verify('nocolon'));
        $this->assertNull($this->service->verify(''));
        $this->assertNull($this->service->verify(null));
    }

    public function testVerifyRejectsUnknownSelector()
    {
        $this->assertNull($this->service->verify('nosuch:validator'));
    }

    public function testVerifyRejectsTamperedValidator()
    {
        $value = $this->service->issue('boshag');
        $parsed = (new SplitToken(new Phpfunc))->parseValue($value);

        $this->assertNull(
            $this->service->verify($parsed['selector'] . ':tampered')
        );
    }

    public function testVerifyThrowsOnExpiredToken()
    {
        $value = $this->service->issue('boshag');
        $parsed = (new SplitToken(new Phpfunc))->parseValue($value);

        // backdate the stored expiry rather than injecting a clock
        $expired_at = time() - 1;
        $this->storage->rows[$parsed['selector']]['expires'] = $expired_at;

        try {
            $this->service->verify($value);
            $this->fail('Expected TokenExpired to be thrown.');
        } catch (TokenExpired $e) {
            $this->assertSame($expired_at, $e->getExpires());
        }
    }

    public function testExpiredTokenWithBadValidatorReturnsNullRatherThanThrowing()
    {
        // the validator is matched before the expiry is looked at, so an
        // attacker holding only a selector cannot learn that it exists
        $value = $this->service->issue('boshag');
        $parsed = (new SplitToken(new Phpfunc))->parseValue($value);
        $this->storage->rows[$parsed['selector']]['expires'] = time() - 1;

        $this->assertNull(
            $this->service->verify($parsed['selector'] . ':tampered')
        );
    }

    public function testFailedVerifyDoesNotDeleteTheToken()
    {
        // a wrong validator must not be a way to revoke somebody else's token
        $value = $this->service->issue('boshag');
        $parsed = (new SplitToken(new Phpfunc))->parseValue($value);

        $this->service->verify($parsed['selector'] . ':tampered');

        $this->assertNotNull($this->storage->findBySelector($parsed['selector']));
        $this->assertNotNull($this->service->verify($value));
    }

    public function testVerifyTouchesTheToken()
    {
        $value = $this->service->issue('boshag');
        $parsed = (new SplitToken(new Phpfunc))->parseValue($value);

        $this->assertNull($this->storage->rows[$parsed['selector']]['last_used_at']);

        $this->service->verify($value);

        $this->assertSame(
            time(),
            $this->storage->rows[$parsed['selector']]['last_used_at']
        );
    }

    public function testRevoke()
    {
        $value = $this->service->issue('boshag');

        $this->assertTrue($this->service->revoke($value));
        $this->assertNull($this->service->verify($value));
    }

    public function testRevokeRejectsTamperedValidator()
    {
        $value = $this->service->issue('boshag');
        $parsed = (new SplitToken(new Phpfunc))->parseValue($value);

        $this->assertFalse(
            $this->service->revoke($parsed['selector'] . ':tampered')
        );

        // the real token still works
        $this->assertNotNull($this->service->verify($value));
    }

    public function testRevokeUnknownTokenReturnsFalse()
    {
        $this->assertFalse($this->service->revoke('nosuch:validator'));
    }

    public function testRevokeWorksOnAnExpiredToken()
    {
        $value = $this->service->issue('boshag');
        $parsed = (new SplitToken(new Phpfunc))->parseValue($value);
        $this->storage->rows[$parsed['selector']]['expires'] = time() - 1;

        $this->assertTrue($this->service->revoke($value));
        $this->assertNull($this->storage->findBySelector($parsed['selector']));
    }

    public function testRevokeBySelector()
    {
        $value = $this->service->issue('boshag');
        $parsed = (new SplitToken(new Phpfunc))->parseValue($value);

        $this->service->revokeBySelector($parsed['selector']);

        $this->assertNull($this->service->verify($value));
    }

    public function testRevokeAll()
    {
        $one = $this->service->issue('boshag');
        $two = $this->service->issue('boshag');
        $other = $this->service->issue('other');

        $this->service->revokeAll('boshag');

        $this->assertNull($this->service->verify($one));
        $this->assertNull($this->service->verify($two));
        $this->assertNotNull($this->service->verify($other));
    }

    public function testDeleteExpired()
    {
        $live = $this->service->issue('boshag');
        $dead = $this->service->issue('boshag');

        $parsed = (new SplitToken(new Phpfunc))->parseValue($dead);
        $this->storage->rows[$parsed['selector']]['expires'] = time() - 1;

        $this->service->deleteExpired();

        $this->assertNotNull($this->service->verify($live));
        $this->assertNull($this->storage->findBySelector($parsed['selector']));
    }
}
