<?php

declare(strict_types=1);

namespace spec\Cowegis\Bundle\Contao\Hydrator;

use Contao\CoreBundle\Cache\CacheTagManager;
use Netzmacht\Contao\Toolkit\Response\ResponseTagger;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use spec\Cowegis\Bundle\Contao\Hydrator\Fixture\ModelStub;
use spec\Cowegis\Bundle\Contao\Hydrator\Fixture\ResponseTaggerPluginHost;
use stdClass;

/**
 * Third-party layer hydrators may still extend LayerTypeHydrator and construct it with the
 * deprecated netzmacht/contao-toolkit ResponseTagger, so the trait must keep accepting it
 * alongside Contao's CacheTagManager.
 */
final class ResponseTaggerPluginSpec extends ObjectBehavior
{
    public function let(): void
    {
        $this->beAnInstanceOf(ResponseTaggerPluginHost::class);
    }

    public function it_tags_the_response_via_the_cache_tag_manager(CacheTagManager $cacheTagManager): void
    {
        $this->beConstructedWith($cacheTagManager);

        $model = new ModelStub(5);

        $this->tagResponseForData($model);

        $cacheTagManager->tagWithModelInstance($model)->shouldHaveBeenCalled();
    }

    public function it_tags_the_response_via_the_deprecated_toolkit_response_tagger(
        ResponseTagger $responseTagger,
    ): void {
        $this->beConstructedWith($responseTagger);

        $model = new ModelStub(5);

        $this->tagResponseForData($model);

        $responseTagger->addTags(['contao.db.tl_cowegis_layer.5'])->shouldHaveBeenCalled();
    }

    public function it_does_not_tag_the_response_for_non_model_data(CacheTagManager $cacheTagManager): void
    {
        $this->beConstructedWith($cacheTagManager);

        $this->tagResponseForData(new stdClass());

        $cacheTagManager->tagWithModelInstance(Argument::any())->shouldNotHaveBeenCalled();
    }
}
