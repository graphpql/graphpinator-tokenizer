<?php

declare(strict_types = 1);

namespace Graphpinator\Exception\Tokenizer;

abstract class TokenizerError extends \Graphpinator\Exception\GraphpinatorBase
{
    final public function __construct(\Graphpinator\Common\Location $location)
    {
        parent::__construct();

        $this->location = $location;
    }

    final protected function isOutputable() : bool
    {
        return true;
    }
}
