<?php

namespace Tigusigalpa\TBankID\Models;

class TBankUser
{
    protected array $attributes;

    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function getId(): ?string
    {
        return $this->attributes['sub'] ?? null;
    }

    public function getEmail(): ?string
    {
        return $this->attributes['email'] ?? null;
    }

    public function isEmailVerified(): bool
    {
        return $this->attributes['email_verified'] ?? false;
    }

    public function getPhone(): ?string
    {
        return $this->attributes['phone_number'] ?? null;
    }

    public function isPhoneVerified(): bool
    {
        return $this->attributes['phone_number_verified'] ?? false;
    }

    public function getName(): ?string
    {
        return $this->attributes['name'] ?? null;
    }

    public function getGivenName(): ?string
    {
        return $this->attributes['given_name'] ?? null;
    }

    public function getFamilyName(): ?string
    {
        return $this->attributes['family_name'] ?? null;
    }

    public function getMiddleName(): ?string
    {
        return $this->attributes['middle_name'] ?? null;
    }

    public function getPreferredUsername(): ?string
    {
        return $this->attributes['preferred_username'] ?? null;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function get(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->attributes[$key]);
    }
}
