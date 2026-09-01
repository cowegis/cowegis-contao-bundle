<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Netzmacht\Contao\Toolkit\Dca\DcaManager;
use Netzmacht\Contao\Toolkit\Dca\Listener\AbstractListener;
use Override;

final class ContentDcaListener extends AbstractListener
{
    public function __construct(DcaManager $dcaManager, private readonly bool $clientAvailable)
    {
        parent::__construct($dcaManager);
    }

    #[Override]
    public static function getName(): string
    {
        return 'tl_content';
    }

    #[AsCallback('tl_content', 'config.onload')]
    public function onLoad(): void
    {
        if (! $this->clientAvailable) {
            return;
        }

        $definition = $this->getDefinition();
        $definition->set(['fields', 'cowegis_client', 'default'], 'client');
    }

    /** @return list<string> */
    #[AsCallback('tl_content', 'fields.cowegis_client.options')]
    public function clientOptions(): array
    {
        $options = ['custom'];

        if ($this->clientAvailable) {
            $options[] = 'client';
        }

        return $options;
    }
}
