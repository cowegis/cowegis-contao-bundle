<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model;

use Contao\Model\Collection;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;

/** @extends ContaoRepository<ControlModel> */
final class ControlRepository extends ContaoRepository
{
    /** @param array<string,mixed> $options */
    public function findActive(int $mapId, array $options = []): Collection|null
    {
        $options['sorting'] ??= '.sorting';

        return $this->findBy(['.pid=?', '.active=?'], [$mapId, 1], $options);
    }
}
