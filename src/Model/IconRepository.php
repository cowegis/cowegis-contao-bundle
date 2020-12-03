<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model;

use Contao\Model\Collection;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;

final class IconRepository extends ContaoRepository
{
    public function findAllActive(): ?Collection
    {
        return $this->findBy(['.active=?'], [1]);
    }
}
