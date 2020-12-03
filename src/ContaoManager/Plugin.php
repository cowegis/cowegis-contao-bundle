<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Config\ConfigInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Cowegis\Bundle\Api\CowegisApiBundle;
use Cowegis\Bundle\Contao\CowegisContaoBundle;
use Cowegis\Bundle\ContaoGeocodeWidget\CowegisContaoGeocodeWidgetBundle;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

final class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
    /** @return ConfigInterface[] */
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(CowegisApiBundle::class),
            BundleConfig::create(CowegisContaoBundle::class)->setLoadAfter([
                ContaoCoreBundle::class,
                CowegisApiBundle::class,
                CowegisContaoGeocodeWidgetBundle::class,
            ]),
        ];
    }

    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): ?RouteCollection
    {
        $routeCollection = new RouteCollection();

        $apiPath = $kernel->getBundle('CowegisApiBundle')->getPath();
        $loader  = $resolver->resolve($apiPath . '/Resources/config/routing.xml');
        if ($loader) {
            $collection = $loader->load($apiPath . '/Resources/config/routing.xml');

            if ($collection instanceof RouteCollection) {
                $routePrefix = $kernel->getContainer()->getParameter('cowegis_api.route_prefix');
                $collection->addPrefix($routePrefix);
                $routeCollection->addCollection($collection);
            }
        }

        $loader = $resolver->resolve(__DIR__ . '/../Resources/config/routing.xml');
        if ($loader) {
            $collection = $loader->load(__DIR__ . '/../Resources/config/routing.xml');
            if ($collection instanceof RouteCollection) {
                $routeCollection->addCollection($collection);
            }
        }

        return $routeCollection;
    }
}
