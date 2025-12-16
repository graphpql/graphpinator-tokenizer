<?php

declare(strict_types = 1);

namespace Graphpinator\Tokenizer\Exception;

use Graphpinator\Common\Location;
use Graphpinator\Exception\GraphpinatorBase;

abstract class TokenizerError extends GraphpinatorBase
{
    final public function __construct(
        Location $location,
    )
    {
        parent::__construct();

        $this->setLocation($location);
    }

    final public function isOutputable() : bool
    {
        return true;
    }
}
