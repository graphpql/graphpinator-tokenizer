<?php

declare(strict_types = 1);

namespace Graphpinator\Tokenizer\Exception;

final class StringLiteralNewLine extends TokenizerError
{
    public const MESSAGE = 'Simple string literal cannot span across multiple lines. Use block literal or escape sequence.';
}
