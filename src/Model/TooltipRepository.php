<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model;

use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;

/** @extends ContaoRepository<TooltipModel> */
final class TooltipRepository extends ContaoRepository
{
    public function __construct()
    {
        parent::__construct(TooltipModel::class);
    }
}
