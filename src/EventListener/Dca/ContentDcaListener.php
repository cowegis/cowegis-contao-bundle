<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use Netzmacht\Contao\Toolkit\Dca\DcaManager;
use Netzmacht\Contao\Toolkit\Dca\Listener\AbstractListener;

final class ContentDcaListener extends AbstractListener
{
    public function __construct(DcaManager $dcaManager, private readonly bool $clientAvailable)
    {
        parent::__construct($dcaManager);
    }

    public static function getName(): string
    {
        return 'tl_content';
    }

    public function onLoad(): void
    {
        if (! $this->clientAvailable) {
            return;
        }

        $definition = $this->getDefinition();
        $definition->set(['fields', 'cowegis_client', 'default'], 'client');
    }

    /** @return list<string> */
    public function clientOptions(): array
    {
        $options = ['custom'];

        if ($this->clientAvailable) {
            $options[] = 'client';
        }

        return $options;
    }
}
