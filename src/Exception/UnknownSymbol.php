<?php

declare(strict_types = 1);

namespace Graphpinator\Tokenizer\Exception;

final class UnknownSymbol extends TokenizerError
{
    public const MESSAGE = 'Unknown symbol.';
}
