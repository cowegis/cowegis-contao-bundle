<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model\Map;

use Cowegis\Bundle\Contao\Model\Map\MapModel;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;

final class MapRepository extends ContaoRepository
{
    public function __construct()
    {
        parent::__construct(MapModel::class);
    }
}
