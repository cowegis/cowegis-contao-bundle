<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Hook;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\Adapter;
use Contao\System;

use function str_starts_with;

#[AsHook('loadLanguageFile')]
final class LanguageFileListener
{
    /** @param Adapter<System> $systemAdapter */
    public function __construct(private readonly Adapter $systemAdapter)
    {
    }

    public function __invoke(string $name, string $currentLanguage): void
    {
        if (! str_starts_with($name, 'tl_cowegis_')) {
            return;
        }

        $this->systemAdapter->loadLanguageFile('cowegis', $currentLanguage);
    }
}
