<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Hydrator;

use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\Model;

trait ResponseTaggerPlugin
{
    public function __construct(private readonly CacheTagManager $cacheTagManager)
    {
    }

    protected function tagResponseForData(mixed $data): void
    {
        if (! $data instanceof Model) {
            return;
        }

        $this->cacheTagManager->tagWithModelInstance($data);
    }
}
