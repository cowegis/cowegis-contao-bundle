<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model;

use Cowegis\Core\Definition\DefinitionId\IntegerDefinitionId;
use Cowegis\Core\Definition\Icon\IconId;

/**
 * @property string type
 */
final class IconModel extends Model
{
    protected static $strTable = 'tl_cowegis_icon';

    public function iconId() : IconId
    {
        return IconId::fromValue(IntegerDefinitionId::fromValue($this->id()));
    }
}
