<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Cowegis\Bundle\Api\CowegisApiBundle;
use Cowegis\Bundle\Client\CowegisClientBundle;
use Cowegis\Bundle\Contao\CowegisContaoBundle;
use Cowegis\Bundle\ContaoGeocodeWidget\CowegisContaoGeocodeWidgetBundle;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

final class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
    public function getBundles(ParserInterface $parser) : array
    {
        return [
            BundleConfig::create(CowegisApiBundle::class),
            BundleConfig::create(CowegisClientBundle::class),
            BundleConfig::create(CowegisContaoBundle::class)->setLoadAfter([
                ContaoCoreBundle::class,
                CowegisApiBundle::class,
                CowegisClientBundle::class,
                CowegisContaoGeocodeWidgetBundle::class,
            ]),
        ];
    }

    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel) : ?RouteCollection
    {
        $loader = $resolver->resolve(__DIR__ . '/Bundle/Contao/Resources/config/routing.xml');
        if ($loader) {
            return $loader->load(__DIR__ . '/Bundle/Contao/Resources/config/routing.xml');
        }

        return null;
    }
}