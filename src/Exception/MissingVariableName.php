<?php

declare(strict_types = 1);

namespace Graphpinator\Tokenizer\Exception;

final class MissingVariableName extends \Graphpinator\Tokenizer\Exception\TokenizerError
{
    public const MESSAGE = 'Missing variable name after $ symbol.';
}
