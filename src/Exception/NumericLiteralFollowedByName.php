<?php

declare(strict_types = 1);

namespace Graphpinator\Tokenizer\Exception;

final class NumericLiteralFollowedByName extends TokenizerError
{
    public const MESSAGE = 'Numeric literal cannot be followed by name.';
}
