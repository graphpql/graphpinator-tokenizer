<?php

declare(strict_types = 1);

namespace Graphpinator\Tokenizer\Exception;

final class StringLiteralWithoutEnd extends \Graphpinator\Tokenizer\Exception\TokenizerError
{
    public const MESSAGE = 'String literal without proper end.';
}
