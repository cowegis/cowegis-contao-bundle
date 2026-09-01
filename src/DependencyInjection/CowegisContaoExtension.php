<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\DependencyInjection;

use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Map\Control\ControlType;
use Cowegis\Bundle\Contao\Map\Icon\IconType;
use Cowegis\Bundle\Contao\Map\Layer\LayerType;
use Cowegis\Bundle\Contao\Map\Style\StyleType;
use Override;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class CowegisContaoExtension extends Extension
{
    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    #[Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $xmlLoader  = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $yamlLoader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $container->registerForAutoconfiguration(Hydrator::class)
            ->addTag('Cowegis\Bundle\Contao\Hydrator\Hydrator');
        $container->registerForAutoconfiguration(LayerType::class)
            ->addTag('Cowegis\Bundle\Contao\Map\Layer\LayerType');
        $container->registerForAutoconfiguration(ControlType::class)
            ->addTag('Cowegis\Bundle\Contao\Map\Control\ControlType');
        $container->registerForAutoconfiguration(IconType::class)
            ->addTag('Cowegis\Bundle\Contao\Map\Icon\IconType');
        $container->registerForAutoconfiguration(StyleType::class)
            ->addTag('Cowegis\Bundle\Contao\Map\Style\StyleType');

        $yamlLoader->load('amenities.yaml');
        $yamlLoader->load('config.yaml');
        $xmlLoader->load('controls.xml');
        $xmlLoader->load('fragments.xml');
        $xmlLoader->load('hydrators.xml');
        $yamlLoader->load('icons.yaml');
        $yamlLoader->load('styles.yaml');
        $xmlLoader->load('layers.xml');
        $xmlLoader->load('listeners.xml');
        $xmlLoader->load('services.xml');
        $xmlLoader->load('repositories.xml');

        /** @psalm-var array<string,string> $bundles */
        $bundles   = $container->getParameter('kernel.bundles');
        $installed = isset($bundles['CowegisClientBundle']);
        $container->setParameter('cowegis_contao.client_bundle', $installed);
    }
}
