<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\ConsentBridge;

use Hofff\Contao\Consent\Bridge\Bridge;
use Hofff\Contao\Consent\Bridge\Plugin as ConsentBridgePlugin;
use Hofff\Contao\Consent\Bridge\Render\RenderInformation;

final class Plugin implements ConsentBridgePlugin
{
    public function load(Bridge $bridge): void
    {
        $renderInformation = RenderInformation::autoRenderWithPlaceholder('cowegis_consent_bridge_placeholder');

        $bridge
            ->supportContentElement('cowegis_map', $renderInformation)
            ->supportFrontendModule('cowegis_map', $renderInformation);
    }
}
