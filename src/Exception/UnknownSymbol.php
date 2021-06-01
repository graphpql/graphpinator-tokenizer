<?php

declare(strict_types = 1);

namespace Graphpinator\Tokenizer\Exception;

final class UnknownSymbol extends \Graphpinator\Tokenizer\Exception\TokenizerError
{
    public const MESSAGE = 'Unknown symbol.';
}
