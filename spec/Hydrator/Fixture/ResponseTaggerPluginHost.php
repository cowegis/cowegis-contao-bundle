<?php

declare(strict_types=1);

namespace spec\Cowegis\Bundle\Contao\Hydrator\Fixture;

use Cowegis\Bundle\Contao\Hydrator\ResponseTaggerPlugin;

final class ResponseTaggerPluginHost
{
    use ResponseTaggerPlugin {
        tagResponseForData as public;
    }
}
