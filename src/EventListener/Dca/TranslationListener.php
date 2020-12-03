<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use Contao\CoreBundle\Framework\Adapter;

final class TranslationListener
{
    /** @var Adapter */
    private $systemAdapter;

    public function __construct(Adapter $systemAdapter)
    {
        $this->systemAdapter = $systemAdapter;
    }

    public function onLoad(): void
    {
        $this->systemAdapter->__call('loadLanguageFile', ['cowegis']);
    }
}
