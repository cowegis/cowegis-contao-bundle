<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model;

use Contao\Model\Collection;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;

/** @extends ContaoRepository<IconModel> */
final class IconRepository extends ContaoRepository
{
    public function findAllActive(): Collection|null
    {
        return $this->findBy(['.active=?'], [1]);
    }
}
