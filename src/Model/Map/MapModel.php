<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model\Map;

use Cowegis\Bundle\Contao\Model\Model;

/**
 * @property mixed|null panes
 */
final class MapModel extends Model
{
    /** @var string */
    protected static $strTable = 'tl_cowegis_map';
}
