<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model;

use Contao\Model\Collection;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;

final class ControlRepository extends ContaoRepository
{
    public function findActive(int $mapId, array $options = []) : ?Collection
    {
        $options['sorting'] = $options['sorting'] ?? '.sorting';

        return $this->findBy(['.pid=?', '.active=?'], [$mapId, 1], $options);
    }
}
