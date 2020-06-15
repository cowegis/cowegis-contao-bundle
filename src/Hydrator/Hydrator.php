<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Hydrator;

use Cowegis\Core\Provider\Context;

interface Hydrator
{
    public function supports(object $data, object $definition) : bool;

    public function hydrate(object $data, object $definition, Context $context) : void;
}
