<?php

namespace Aura\Auth;


class UserTest extends \PHPUnit_Framework_TestCase
{
    public function test__clone()
    {
        $usr = new User;
        $arr = [
            'username'  => 'jane',
            'full_name' => 'Jane Doe',
            'email'     => 'jane@example.com',
            'uri'       => 'jane.example.com',
            'avatar'    => 'jane.example.com/pic.jpg',
            'unique_id' => 'jane'
        ];

        $usr->setFromArray($arr);

        $this->assertSame($arr['username'],  $usr->username);
        $this->assertSame($arr['full_name'], $usr->full_name);
        $this->assertSame($arr['email'],     $usr->email);
        $this->assertSame($arr['uri'],       $usr->uri);
        $this->assertSame($arr['avatar'],    $usr->avatar);
        $this->assertSame($arr['unique_id'], $usr->unique_id);

        $cloned = clone $usr;

        $this->assertEmpty($cloned->username);
        $this->assertEmpty($cloned->full_name);
        $this->assertEmpty($cloned->email);
        $this->assertEmpty($cloned->uri);
        $this->assertEmpty($cloned->avatar);
        $this->assertEmpty($cloned->unique_id);
    }

    public function test__sleep()
    {
        $usr      = new User;
        $expected = ['username', 'full_name', 'email', 'uri', 'avatar', 'unique_id'];

        $this->assertEquals($expected, $usr->__sleep());
    }

    public function testSetFromArray()
    {
        $usr = new User;
        $arr = [
            'username'  => 'jane',
            'full_name' => 'Jane Doe',
            'email'     => 'jane@example.com',
            'uri'       => 'jane.example.com',
            'avatar'    => 'jane.example.com/pic.jpg',
            'unique_id' => 'jane'
        ];

        $usr->setFromArray($arr);

        $this->assertSame($arr['username'],  $usr->username);
        $this->assertSame($arr['full_name'], $usr->full_name);
        $this->assertSame($arr['email'],     $usr->email);
        $this->assertSame($arr['uri'],       $usr->uri);
        $this->assertSame($arr['avatar'],    $usr->avatar);
        $this->assertSame($arr['unique_id'], $usr->unique_id);
    }

    public function testSetFromArrayNoUsernameException()
    {
        $this->setExpectedException('\Aura\Auth\Exception');

        $usr = new User;
        $arr = [
            'full_name' => 'Jane Doe',
            'email'     => 'jane@example.com',
            'uri'       => 'jane.example.com',
            'avatar'    => 'jane.example.com/pic.jpg',

        ];

        $usr->setFromArray($arr);
    }
}