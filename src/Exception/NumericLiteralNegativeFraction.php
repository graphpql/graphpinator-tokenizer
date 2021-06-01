<?php

declare(strict_types = 1);

namespace Graphpinator\Tokenizer\Exception;

final class NumericLiteralNegativeFraction extends \Graphpinator\Tokenizer\Exception\TokenizerError
{
    public const MESSAGE = 'Negative fraction part in numeric value.';
}
