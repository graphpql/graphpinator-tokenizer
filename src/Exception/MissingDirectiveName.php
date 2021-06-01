<?php

declare(strict_types = 1);

namespace Graphpinator\Tokenizer\Exception;

final class MissingDirectiveName extends \Graphpinator\Tokenizer\Exception\TokenizerError
{
    public const MESSAGE = 'Missing directive name after @ symbol.';
}
