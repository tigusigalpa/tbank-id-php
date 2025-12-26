<?php

namespace Tigusigalpa\TBankID\Tests;

use PHPUnit\Framework\TestCase;
use Tigusigalpa\TBankID\Models\TBankUser;

class TBankUserTest extends TestCase
{
    protected array $userData;
    protected TBankUser $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userData = [
            'sub' => 'user123',
            'email' => 'test@example.com',
            'email_verified' => true,
            'phone_number' => '+79001234567',
            'phone_number_verified' => true,
            'name' => 'Ivan Ivanov',
            'given_name' => 'Ivan',
            'family_name' => 'Ivanov',
            'middle_name' => 'Ivanovich',
            'preferred_username' => 'ivan',
        ];

        $this->user = new TBankUser($this->userData);
    }

    public function testGetId(): void
    {
        $this->assertEquals('user123', $this->user->getId());
    }

    public function testGetEmail(): void
    {
        $this->assertEquals('test@example.com', $this->user->getEmail());
    }

    public function testIsEmailVerified(): void
    {
        $this->assertTrue($this->user->isEmailVerified());
    }

    public function testGetPhone(): void
    {
        $this->assertEquals('+79001234567', $this->user->getPhone());
    }

    public function testIsPhoneVerified(): void
    {
        $this->assertTrue($this->user->isPhoneVerified());
    }

    public function testGetName(): void
    {
        $this->assertEquals('Ivan Ivanov', $this->user->getName());
    }

    public function testGetGivenName(): void
    {
        $this->assertEquals('Ivan', $this->user->getGivenName());
    }

    public function testGetFamilyName(): void
    {
        $this->assertEquals('Ivanov', $this->user->getFamilyName());
    }

    public function testGetMiddleName(): void
    {
        $this->assertEquals('Ivanovich', $this->user->getMiddleName());
    }

    public function testGetPreferredUsername(): void
    {
        $this->assertEquals('ivan', $this->user->getPreferredUsername());
    }

    public function testToArray(): void
    {
        $this->assertEquals($this->userData, $this->user->toArray());
    }

    public function testGet(): void
    {
        $this->assertEquals('user123', $this->user->get('sub'));
        $this->assertEquals('default', $this->user->get('non_existent', 'default'));
    }

    public function testHas(): void
    {
        $this->assertTrue($this->user->has('sub'));
        $this->assertFalse($this->user->has('non_existent'));
    }
}
