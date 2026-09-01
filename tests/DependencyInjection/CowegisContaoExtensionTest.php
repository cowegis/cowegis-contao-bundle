<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Test\DependencyInjection;

use Cowegis\Bundle\Contao\Hydrator\DelegatingHydrator;
use Cowegis\Bundle\Contao\Provider\ContaoBackendProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

use function array_keys;
use function sort;

final class CowegisContaoExtensionTest extends TestCase
{
    private const string HYDRATOR_TAG            = 'Cowegis\Bundle\Contao\Hydrator\Hydrator';
    private const string LAYER_TYPE_TAG          = 'Cowegis\Bundle\Contao\Map\Layer\LayerType';
    private const string CONTROL_TYPE_TAG        = 'Cowegis\Bundle\Contao\Map\Control\ControlType';
    private const string ICON_TYPE_TAG           = 'Cowegis\Bundle\Contao\Map\Icon\IconType';
    private const string STYLE_TYPE_TAG          = 'Cowegis\Bundle\Contao\Map\Style\StyleType';
    private const string SERIALIZER_TAG          = 'Cowegis\Core\Serializer\Serializer';
    private const string LAYER_DATA_PROVIDER_TAG = 'Cowegis\Bundle\Contao\Provider\LayerDataProvider';
    private const string REPOSITORY_TAG          = 'netzmacht.contao_toolkit.repository';
    private const string LAYER_SCHEMA_TAG        = 'Cowegis\Core\Schema\LayerSchemaDescriber';

    private static function compiledContainer(): ContainerBuilder
    {
        $container = StubContainerFactory::create();
        StubContainerFactory::compile($container);

        return $container;
    }

    public function testContainerCompiles(): void
    {
        $this->expectNotToPerformAssertions();
        self::compiledContainer();
    }

    public function testMapActionArgumentCount(): void
    {
        $container = self::compiledContainer();

        self::assertCount(
            10,
            $container->getDefinition('Cowegis\Bundle\Contao\Action\MapContentElementAction')->getArguments(),
        );
        self::assertCount(
            10,
            $container->getDefinition('Cowegis\Bundle\Contao\Action\MapModuleAction')->getArguments(),
        );
    }

    public function testParametersFromYaml(): void
    {
        $container = self::compiledContainer();

        $amenities = $container->getParameter('cowegis_contao.amenities');
        self::assertIsArray($amenities);
        self::assertCount(196, $amenities);
        self::assertSame('administration', $amenities[0]);
        self::assertSame('youth_centre', $amenities[195]);
        self::assertContains('Kneippbecken', $amenities);

        self::assertSame(
            [
                'gpx' => ['gpx'],
                'kml' => ['kml'],
                'wkt' => ['wkt'],
                'geojson' => ['json', 'geojson'],
                'topojson' => ['json', 'geojson'],
            ],
            $container->getParameter('cowegis_contao.file_formats'),
        );
    }

    public function testMissingRequiredServiceFailsCompilation(): void
    {
        $container = StubContainerFactory::create();
        $container->removeDefinition('twig'); // required `@service` arg of Action\Backend\DocsAction

        $this->expectException(ServiceNotFoundException::class);
        StubContainerFactory::compile($container);
    }

    public function testCoreServicesAreRegistered(): void
    {
        $container = self::compiledContainer();

        self::assertTrue($container->hasDefinition(ContaoBackendProvider::class));
        self::assertSame(
            DelegatingHydrator::class,
            $container->getDefinition(self::HYDRATOR_TAG)->getClass(),
        );
        self::assertTrue($container->hasDefinition('Cowegis\Bundle\Contao\Map\MapHydrator'));

        $registryIds = [
            'Cowegis\Bundle\Contao\Map\Layer\LayerTypeRegistry',
            'Cowegis\Bundle\Contao\Map\Control\ControlTypeRegistry',
            'Cowegis\Bundle\Contao\Map\Icon\IconTypeRegistry',
            'Cowegis\Bundle\Contao\Map\Style\StyleTypeRegistry',
        ];

        foreach ($registryIds as $id) {
            self::assertTrue($container->hasDefinition($id), $id);
        }
    }

    public function testPublicFlags(): void
    {
        $container = self::compiledContainer();

        self::assertTrue($container->getDefinition('Cowegis\Bundle\Contao\Action\Backend\DocsAction')->isPublic());
        self::assertTrue($container->getDefinition('Cowegis\Bundle\Contao\Action\Backend\MapLayerAction')->isPublic());
        self::assertTrue(
            $container->getDefinition('Cowegis\Bundle\Contao\EventListener\Dca\LayerDcaListener')->isPublic(),
        );
        self::assertFalse($container->getDefinition('Cowegis\Bundle\Contao\Map\Layer\LayerTypeRegistry')->isPublic());
    }

    public function testDelegatingHydratorIsNotItselfTagged(): void
    {
        $container = self::compiledContainer();

        self::assertSame(
            [],
            $container->getDefinition(self::HYDRATOR_TAG)->getTag(self::HYDRATOR_TAG),
        );
    }

    public function testHydratorTagCollection(): void
    {
        $container = self::compiledContainer();
        $ids       = array_keys($container->findTaggedServiceIds(self::HYDRATOR_TAG));
        sort($ids);

        self::assertSame(self::expectedHydratorIds(), $ids);
    }

    public function testEveryHydratorTaggedExactlyOnce(): void
    {
        $container = self::compiledContainer();

        foreach ($container->findTaggedServiceIds(self::HYDRATOR_TAG) as $id => $tags) {
            self::assertCount(1, $tags, $id . ' must carry the Hydrator tag exactly once');
        }
    }

    public function testHydratorPriorities(): void
    {
        $container = self::compiledContainer();
        $tagged    = $container->findTaggedServiceIds(self::HYDRATOR_TAG);

        // Locate/Bounds/EventDispatching hydrators opt out of autoconfiguration and carry a single
        // explicit priority tag (hydrators.yaml: autoconfigure: false + tags: [...]).
        self::assertSame(-32, $tagged['Cowegis\Bundle\Contao\Map\Options\LocateOptionsHydrator'][0]['priority'] ?? 0);
        self::assertSame(-32, $tagged['Cowegis\Bundle\Contao\Map\Options\BoundsOptionsHydrator'][0]['priority'] ?? 0);
        self::assertSame(
            -128,
            $tagged['Cowegis\Bundle\Contao\Hydrator\EventDispatchingHydrator'][0]['priority'] ?? 0,
        );
    }

    public function testTypeTagCounts(): void
    {
        $container = self::compiledContainer();

        self::assertCount(8, $container->findTaggedServiceIds(self::LAYER_TYPE_TAG));
        self::assertCount(7, $container->findTaggedServiceIds(self::CONTROL_TYPE_TAG));
        self::assertCount(4, $container->findTaggedServiceIds(self::ICON_TYPE_TAG));
        self::assertCount(1, $container->findTaggedServiceIds(self::STYLE_TYPE_TAG));
    }

    public function testMarkerInterfaceServicesTaggedExactlyOnce(): void
    {
        $container = self::compiledContainer();

        self::assertCount(
            1,
            $container->getDefinition('Cowegis\Bundle\Contao\Map\Style\Fixed\FixedStyleType')
                ->getTag(self::STYLE_TYPE_TAG),
        );
        self::assertCount(
            1,
            $container->getDefinition('Cowegis\Bundle\Contao\Map\Style\Fixed\FixedStyleTypeHydrator')
                ->getTag(self::HYDRATOR_TAG),
        );
        self::assertCount(
            1,
            $container->getDefinition('Cowegis\Bundle\Contao\Map\Icon\Image\ImageIconType')
                ->getTag(self::ICON_TYPE_TAG),
        );
        self::assertCount(
            1,
            $container->getDefinition('Cowegis\Bundle\Contao\Map\Icon\Image\ImageIconHydrator')
                ->getTag(self::HYDRATOR_TAG),
        );
        self::assertCount(
            1,
            $container->getDefinition('Cowegis\Bundle\Contao\Map\Control\Zoom\ZoomControlType')
                ->getTag(self::CONTROL_TYPE_TAG),
        );
        self::assertCount(
            1,
            $container->getDefinition('Cowegis\Bundle\Contao\Map\Control\Zoom\ZoomControlHydrator')
                ->getTag(self::HYDRATOR_TAG),
        );

        $serializerTags = $container->getDefinition('Cowegis\Core\Serializer\Control\ZoomControlSerializer')
            ->getTag(self::SERIALIZER_TAG);
        self::assertCount(1, $serializerTags);
        self::assertSame('Cowegis\Core\Definition\Control\ZoomControl', $serializerTags[0]['key'] ?? null);

        self::assertCount(
            1,
            $container->getDefinition('Cowegis\Bundle\Contao\Map\Layer\Tile\TileLayerType')
                ->getTag(self::LAYER_TYPE_TAG),
        );
        self::assertCount(
            1,
            $container->getDefinition('Cowegis\Bundle\Contao\Map\Layer\Tile\TileLayerHydrator')
                ->getTag(self::HYDRATOR_TAG),
        );

        $dataSerializerTags = $container->getDefinition('Cowegis\Core\Serializer\Layer\DataLayerSerializer')
            ->getTag(self::SERIALIZER_TAG);
        self::assertCount(1, $dataSerializerTags);
        self::assertSame('Cowegis\Core\Definition\Layer\DataLayer', $dataSerializerTags[0]['key'] ?? null);
    }

    public function testLayerSchemaDescriberTagCount(): void
    {
        $container = self::compiledContainer();

        // 6 core Cowegis\Core\Schema\Layer\*SchemaDescriber (tagged via `_instanceof`, they extend the
        // abstract Cowegis\Core\Schema\LayerSchemaDescriber) + 1 bundle LayersSchemaDescriber (explicit
        // tag, it implements Cowegis\Core\Schema\SchemaDescriber, not LayerSchemaDescriber).
        self::assertCount(7, $container->findTaggedServiceIds(self::LAYER_SCHEMA_TAG));
    }

    public function testSerializerKeyTag(): void
    {
        $container = self::compiledContainer();
        $tags      = $container->getDefinition('Cowegis\Core\Serializer\Layer\TileLayerSerializer')
            ->getTag(self::SERIALIZER_TAG);

        self::assertSame('Cowegis\Core\Definition\Layer\TileLayer', $tags[0]['key'] ?? null);
    }

    public function testLayerDataProviderTypes(): void
    {
        $container = self::compiledContainer();
        $types     = [];
        foreach ($container->findTaggedServiceIds(self::LAYER_DATA_PROVIDER_TAG) as $tags) {
            $types[] = $tags[0]['type'] ?? null;
        }

        sort($types);

        self::assertSame(['markers', 'reference', 'vectors'], $types);
    }

    public function testRepositoryTagCount(): void
    {
        $container = self::compiledContainer();

        self::assertCount(10, $container->findTaggedServiceIds(self::REPOSITORY_TAG));
    }

    /** @return list<string> */
    private static function expectedHydratorIds(): array
    {
        $ids = [
            'Cowegis\Bundle\Contao\Hydrator\EventDispatchingHydrator',
            'Cowegis\Bundle\Contao\Map\MapHydrator',
            'Cowegis\Bundle\Contao\Map\Options\MapOptionsHydrator',
            'Cowegis\Bundle\Contao\Map\Options\LocateOptionsHydrator',
            'Cowegis\Bundle\Contao\Map\Options\BoundsOptionsHydrator',
            'Cowegis\Bundle\Contao\Map\Options\ViewHydrator',
            'Cowegis\Bundle\Contao\Map\Layer\LayerObjectOptionsHydrator',
            'Cowegis\Bundle\Contao\Map\Layer\GridLayerOptionsHydrator',
            'Cowegis\Bundle\Contao\Map\Presets\PopupPresetHydrator',
            'Cowegis\Bundle\Contao\Map\Presets\TooltipPresetHydrator',
            'Cowegis\Bundle\Contao\Map\Layer\Tile\TileLayerHydrator',
            'Cowegis\Bundle\Contao\Map\Layer\Markers\Hydrator\MarkerOptionsHydrator',
            'Cowegis\Bundle\Contao\Map\Layer\Markers\Hydrator\MarkersLayerHydrator',
            'Cowegis\Bundle\Contao\Map\Layer\Markers\Hydrator\MarkerHydrator',
            'Cowegis\Bundle\Contao\Map\Layer\File\FileLayerHydrator',
            'Cowegis\Bundle\Contao\Map\Layer\Group\GroupLayerHydrator',
            'Cowegis\Bundle\Contao\Map\Layer\MarkerCluster\MarkerClusterGroupHydrator',
            'Cowegis\Bundle\Contao\Map\Layer\Reference\ReferenceLayerHydrator',
            'Cowegis\Bundle\Contao\Map\Layer\Overpass\OverpassLayerHydrator',
            'Cowegis\Bundle\Contao\Map\Layer\Vector\VectorsLayerHydrator',
            'Cowegis\Bundle\Contao\Map\Control\Attribution\AttributionControlHydrator',
            'Cowegis\Bundle\Contao\Map\Control\Fullscreen\FullscreenControlTypeHydrator',
            'Cowegis\Bundle\Contao\Map\Control\Layers\LayersControlHydrator',
            'Cowegis\Bundle\Contao\Map\Control\Loading\LoadingControlHydrator',
            'Cowegis\Bundle\Contao\Map\Control\Scale\ScaleControlHydrator',
            'Cowegis\Bundle\Contao\Map\Control\Zoom\ZoomControlHydrator',
            'Cowegis\Bundle\Contao\Map\Control\Geocoder\GeocoderControlTypeHydrator',
            'Cowegis\Bundle\Contao\Map\Icon\Image\ImageIconHydrator',
            'Cowegis\Bundle\Contao\Map\Icon\Div\DivIconHydrator',
            'Cowegis\Bundle\Contao\Map\Icon\Svg\SvgIconHydrator',
            'Cowegis\Bundle\Contao\Map\Icon\FontAwesome\FontAwesomeIconHydrator',
            'Cowegis\Bundle\Contao\Map\Style\Fixed\FixedStyleTypeHydrator',
        ];
        sort($ids);

        return $ids;
    }
}
