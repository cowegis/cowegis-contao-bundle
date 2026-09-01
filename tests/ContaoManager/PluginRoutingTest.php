<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Test\ContaoManager;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Route;

use function dirname;

final class PluginRoutingTest extends TestCase
{
    public function testBundleRoutingYamlLoads(): void
    {
        $dir        = dirname(__DIR__, 2) . '/src/Resources/config';
        $loader     = new YamlFileLoader(new FileLocator([$dir]));
        $collection = $loader->load('routing.yaml');

        self::assertCount(2, $collection);

        $docsRoute       = $collection->get('cowegis_contao_backend_api_docs');
        $mapLayerActions = $collection->get('cowegis_contao_backend_map_layer_actions');

        self::assertInstanceOf(Route::class, $docsRoute);
        self::assertInstanceOf(Route::class, $mapLayerActions);

        self::assertSame('/contao/cowegis/docs', $docsRoute->getPath());
        self::assertSame(['GET'], $docsRoute->getMethods());
        self::assertSame('backend', $docsRoute->getDefault('_scope'));
        self::assertSame('cowegis-api-docs', $docsRoute->getDefault('_backend_module'));

        self::assertSame('/contao/cowegis/map/{mapId}/layer/{layerId}', $mapLayerActions->getPath());
        self::assertSame(['POST'], $mapLayerActions->getMethods());
        self::assertSame('backend', $mapLayerActions->getDefault('_scope'));
    }
}
