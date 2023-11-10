<?php

declare(strict_types = 1);

namespace Graphpinator\Tokenizer\Exception;

abstract class TokenizerError extends \Graphpinator\Exception\GraphpinatorBase
{
    final public function __construct(\Graphpinator\Common\Location $location)
    {
        parent::__construct();

        $this->setLocation($location);
    }

    final public function isOutputable() : bool
    {
        return true;
    }
}
