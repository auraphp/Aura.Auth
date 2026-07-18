<?php
namespace Aura\Auth\Remember;

use Aura\Auth\Phpfunc;

class TokenTest extends \PHPUnit\Framework\TestCase
{
    protected $token;

    protected function setUp() : void
    {
        $this->token = new Token(new Phpfunc);
    }

    public function testNewSelectorAndValidatorAreRandomHex()
    {
        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $this->token->newSelector());
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $this->token->newValidator());
        $this->assertNotSame($this->token->newSelector(), $this->token->newSelector());
    }

    public function testCookieValueRoundTrip()
    {
        $value = $this->token->makeCookieValue('sel', 'val');
        $this->assertSame('sel:val', $value);

        $parsed = $this->token->parseCookieValue($value);
        $this->assertSame('sel', $parsed['selector']);
        $this->assertSame('val', $parsed['validator']);
    }

    public function testParseRejectsMalformedValues()
    {
        $this->assertNull($this->token->parseCookieValue(null));
        $this->assertNull($this->token->parseCookieValue('nocolon'));
        $this->assertNull($this->token->parseCookieValue(':novalidator'));
        $this->assertNull($this->token->parseCookieValue('noselector:'));
    }

    public function testVerify()
    {
        $validator = $this->token->newValidator();
        $hash = $this->token->hash($validator);

        $this->assertTrue($this->token->verify($hash, $validator));
        $this->assertFalse($this->token->verify($hash, 'tampered'));
    }
}
