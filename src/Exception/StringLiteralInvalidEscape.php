<?php

declare(strict_types = 1);

namespace Graphpinator\Tokenizer\Exception;

final class StringLiteralInvalidEscape extends TokenizerError
{
    public const MESSAGE = 'String literal with invalid escape sequence.';
}
