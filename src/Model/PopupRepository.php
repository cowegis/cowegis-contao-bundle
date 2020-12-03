<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model;

use Contao\Model\Collection;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;

final class PopupRepository extends ContaoRepository
{
    public function __construct()
    {
        parent::__construct(PopupModel::class);
    }

    public function findAllActive(): ?Collection
    {
        return $this->findBy(['.active=?'], [1]);
    }
}
