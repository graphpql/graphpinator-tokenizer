<?php

declare(strict_types = 1);

namespace Graphpinator\Tokenizer\Exception;

final class NumericLiteralMalformed extends TokenizerError
{
    public const MESSAGE = 'Numeric literal incorrectly formed.';
}
