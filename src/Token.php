<?php

declare(strict_types = 1);

namespace Graphpinator\Tokenizer;

final class Token
{
    public function __construct(
        private TokenType $type,
        private \Graphpinator\Common\Location $location,
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

    public function getLocation() : \Graphpinator\Common\Location
    {
        return $this->location;
    }
}
