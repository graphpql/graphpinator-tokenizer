<?php

declare(strict_types = 1);

namespace Graphpinator\Tokenizer;

use Graphpinator\Common\Location;

final class Token
{
    public function __construct(
        private TokenType $type,
        private Location $location,
        private ?string $value = null,
    )
    {
    }

    public function getType() : TokenType
    {
        return $this->type;
    }

    public function getValue() : ?string
    {
        return $this->value;
    }

    public function getLocation() : Location
    {
        return $this->location;
    }
}
