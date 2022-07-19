<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Hook;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\System;

use function strpos;

/** @Hook("loadLanguageFile") */
final class LanguageFileListener
{
    /** @var Adapter<System> */
    private Adapter $systemAdapter;

    /**
     * @param Adapter<System> $systemAdapter
     */
    public function __construct(Adapter $systemAdapter)
    {
        $this->systemAdapter = $systemAdapter;
    }

    public function __invoke(string $name, string $currentLanguage): void
    {
        if (strpos($name, 'tl_cowegis_') !== 0) {
            return;
        }

        $this->systemAdapter->loadLanguageFile('cowegis', $currentLanguage);
    }
}
