<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model;

use Cowegis\Core\Definition\Control\ControlId;
use Cowegis\Core\Definition\DefinitionId\IntegerDefinitionId;

class ControlModel extends Model
{
    /** @var string */
    protected static $strTable = 'tl_cowegis_control';

    public function controlId(): ControlId
    {
        return ControlId::fromValue(IntegerDefinitionId::fromValue($this->id()));
    }
}
