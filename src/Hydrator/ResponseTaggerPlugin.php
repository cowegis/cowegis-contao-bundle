<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Hydrator;

use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\Model;
use Netzmacht\Contao\Toolkit\Response\ResponseTagger;

use function sprintf;

trait ResponseTaggerPlugin
{
    /**
     * The deprecated ResponseTagger is still accepted for BC: third-party layer hydrators may
     * extend LayerTypeHydrator and construct it with that service instead of CacheTagManager.
     */
    public function __construct(private readonly CacheTagManager|ResponseTagger $cacheTagManager)
    {
    }

    protected function tagResponseForData(mixed $data): void
    {
        if (! $data instanceof Model) {
            return;
        }

        if ($this->cacheTagManager instanceof ResponseTagger) {
            $this->cacheTagManager->addTags([sprintf('contao.db.%s.%s', $data::getTable(), $data->id)]);

            return;
        }

        $this->cacheTagManager->tagWithModelInstance($data);
    }
}
