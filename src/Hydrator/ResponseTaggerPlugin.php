<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Hydrator;

use Contao\Model as ContaoModel;
use Cowegis\Bundle\Contao\Model\Model as CowegisModel;
use Netzmacht\Contao\Toolkit\Response\ResponseTagger;

use function sprintf;

trait ResponseTaggerPlugin
{
    private ResponseTagger $responseTagger;

    public function __construct(ResponseTagger $responseTagger)
    {
        $this->responseTagger = $responseTagger;
    }

    /**
     * @param mixed $data
     */
    protected function tagResponseForData($data): void
    {
        if ($data instanceof CowegisModel) {
            $this->tagResponse(sprintf('contao.db.%s.%s', $data::getTable(), $data->id()));
        } elseif ($data instanceof ContaoModel) {
            $this->tagResponse(sprintf('contao.db.%s.%s', $data::getTable(), $data->id));
        }
    }

    protected function tagResponse(string ...$tags): void
    {
        $this->responseTagger->addTags($tags);
    }
}
