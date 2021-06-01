<?php

declare(strict_types = 1);

namespace Graphpinator\Tokenizer\Exception;

final class InvalidEllipsis extends \Graphpinator\Tokenizer\Exception\TokenizerError
{
    public const MESSAGE = 'Invalid ellipsis - three dots are expected for ellipsis.';
}
