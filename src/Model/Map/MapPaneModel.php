<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model\Map;

use Cowegis\Bundle\Contao\Model\Model;

/**
 * @property string $name
 */
final class MapPaneModel extends Model
{
    /** @var string */
    protected static $strTable = 'tl_cowegis_map_pane';
}
