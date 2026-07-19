<?php
namespace Aura\Auth\Token;

use Aura\Auth\Phpfunc;

class SplitTokenTest extends \PHPUnit\Framework\TestCase
{
    protected $token;

    protected function setUp() : void
    {
        $this->token = new SplitToken(new Phpfunc);
    }

    public function testNewSelectorAndValidatorAreRandomHex()
    {
        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $this->token->newSelector());
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $this->token->newValidator());
        $this->assertNotSame($this->token->newSelector(), $this->token->newSelector());
    }

    public function testValueRoundTrip()
    {
        $value = $this->token->makeValue('sel', 'val');
        $this->assertSame('sel:val', $value);

        $parsed = $this->token->parseValue($value);
        $this->assertSame('sel', $parsed['selector']);
        $this->assertSame('val', $parsed['validator']);
    }

    public function testParseRejectsMalformedValues()
    {
        $this->assertNull($this->token->parseValue(null));
        $this->assertNull($this->token->parseValue('nocolon'));
        $this->assertNull($this->token->parseValue(':novalidator'));
        $this->assertNull($this->token->parseValue('noselector:'));
    }

    public function testVerify()
    {
        $validator = $this->token->newValidator();
        $hash = $this->token->hash($validator);

        $this->assertTrue($this->token->verify($hash, $validator));
        $this->assertFalse($this->token->verify($hash, 'tampered'));
    }
}
