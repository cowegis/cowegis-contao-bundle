<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class CowegisContaoExtension extends Extension
{
    /** @param array<int, array<string,mixed>> $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load('amenities.xml');
        $loader->load('config.xml');
        $loader->load('controls.xml');
        $loader->load('fragments.xml');
        $loader->load('hydrators.xml');
        $loader->load('icons.xml');
        $loader->load('layers.xml');
        $loader->load('listeners.xml');
        $loader->load('services.xml');
        $loader->load('repositories.xml');
    }
}
