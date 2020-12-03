<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model\Map;

use Contao\Model\Collection;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;

final class MapPaneRepository extends ContaoRepository
{
    public function __construct()
    {
        parent::__construct(MapPaneModel::class);
    }

    /** @param array<string,mixed> $options */
    public function findByMap(int $mapId, array $options = []): ?Collection
    {
        $options['sorting'] = $options['sorting'] ?? '.sorting';

        return $this->findBy(['.pid=?'], [$mapId], $options);
    }
}
