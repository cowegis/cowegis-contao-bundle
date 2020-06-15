<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Style;

use Cowegis\Bundle\Contao\Model\StyleModel;
use Cowegis\Core\Definition\DefinitionId;

interface StyleType
{
    public function name() : string;

    public function createDefinition(StyleModel $styleModel) : DefinitionId;
}
