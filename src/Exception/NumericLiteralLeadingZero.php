<?php

declare(strict_types = 1);

namespace Graphpinator\Tokenizer\Exception;

final class NumericLiteralLeadingZero extends \Graphpinator\Tokenizer\Exception\TokenizerError
{
    public const MESSAGE = 'Numeric literal with leading zeroes.';
}
