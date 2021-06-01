<?php

declare(strict_types = 1);

namespace Graphpinator\Tokenizer\Exception;

final class StringLiteralInvalidEscape extends \Graphpinator\Tokenizer\Exception\TokenizerError
{
    public const MESSAGE = 'String literal with invalid escape sequence.';
}
