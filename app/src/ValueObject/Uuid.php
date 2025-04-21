<?php

declare(strict_types=1);

namespace App\ValueObject;

use Ramsey\Uuid\Uuid as RamseyUuid;
use Ramsey\Uuid\UuidInterface as RamseyUuidInterface;

class Uuid
{
    protected readonly RamseyUuidInterface $value;

    public function __construct(string $uuid = null)
    {
        $this->value = $uuid ? RamseyUuid::fromString($uuid) : RamseyUuid::uuid4();
    }

    public function value(): string
    {
        return $this->value->toString();
    }

    public function __toString(): string
    {
        return $this->value->toString();
    }

    public function equals(Uuid $other): bool
    {
        return $this->value->equals($other->value);
    }

    public static function generate(): self
    {
        return new static(RamseyUuid::uuid4()->toString());
    }
}
