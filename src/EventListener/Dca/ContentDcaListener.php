<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use Netzmacht\Contao\Toolkit\Dca\DcaManager;
use Netzmacht\Contao\Toolkit\Dca\Listener\AbstractListener;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookup;

final class ContentDcaListener extends AbstractListener
{
    protected static $name = 'tl_content';

    /** @var bool */
    private $clientAvailable;

    public function __construct(DcaManager $dcaManager, bool $clientAvailable)
    {
        parent::__construct($dcaManager);

        $this->clientAvailable = $clientAvailable;
    }

    public function onLoad(): void
    {
        if (!$this->clientAvailable) {
            return;
        }

        $definition = $this->getDefinition();
        $definition->set(['fields', 'cowegis_client', 'default'], 'client');
    }

    public function clientOptions(): array
    {
        $options = ['custom'];

        if ($this->clientAvailable) {
            $options[] = 'client';
        }

        return $options;
    }
}
