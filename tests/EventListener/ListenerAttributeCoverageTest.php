<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Test\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Cowegis\Bundle\Contao\EventListener\BackendMenuListener;
use Cowegis\Bundle\Contao\EventListener\BackendStyleListener;
use Cowegis\Bundle\Contao\EventListener\Filter\ApplyFilterRuleMarkerListener;
use Cowegis\Bundle\Contao\EventListener\LayerResponseListener;
use Cowegis\Bundle\Contao\EventListener\MapResponseListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

use function array_map;
use function in_array;
use function sort;

final class ListenerAttributeCoverageTest extends TestCase
{
    /** @return array<string, list<array{string, string}>> table+target pairs */
    private static function goldenCallbacks(): array
    {
        return [
            'Cowegis\Bundle\Contao\EventListener\Dca\ContentDcaListener' => [
                ['tl_content', 'config.onload'],
                ['tl_content', 'fields.cowegis_client.options'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\ModuleDcaListener' => [
                ['tl_module', 'config.onload'],
                ['tl_module', 'fields.cowegis_client.options'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\LayerDcaListener' => [
                ['tl_cowegis_layer', 'fields.type.options'],
                ['tl_cowegis_layer', 'fields.fileFormat.options'],
                ['tl_cowegis_layer', 'fields.amenityIcons.eval.columnFields.amenity.options'],
                ['tl_cowegis_layer', 'fields.file.load'],
                ['tl_cowegis_layer', 'list.label.label'],
                ['tl_cowegis_layer', 'list.operations.data.button'],
                ['tl_cowegis_layer', 'list.sorting.paste_button'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\MapLayerSelectionDcaListener' => [
                ['tl_cowegis_layer', 'config.onload'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\MapDcaListener' => [
                ['tl_cowegis_map', 'fields.layers.eval.listCallback'],
                ['tl_cowegis_map', 'config.onload'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\MapLayerDcaListener' => [
                ['tl_cowegis_map_layer', 'config.onload'],
                ['tl_cowegis_map_layer', 'list.sorting.child_record'],
                ['tl_cowegis_map_layer', 'fields.pane.options'],
                ['tl_cowegis_map_layer', 'fields.dataPane.options'],
                ['tl_cowegis_map_layer', 'fields.filterRules.options'],
                ['tl_cowegis_map_layer', 'fields.layerId.input_field'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\MapPaneDcaListener' => [
                ['tl_cowegis_map_pane', 'list.sorting.child_record'],
                ['tl_cowegis_map_pane', 'fields.name.save'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\MarkerDcaListener' => [
                ['tl_cowegis_marker', 'list.sorting.child_record'],
                ['tl_cowegis_marker', 'fields.coordinates.save'],
                ['tl_cowegis_marker', 'fields.coordinates.load'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\IconDcaListener' => [
                ['tl_cowegis_icon', 'fields.type.options'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\ControlDcaListener' => [
                ['tl_cowegis_control', 'list.sorting.child_record'],
                ['tl_cowegis_control', 'fields.type.options'],
                ['tl_cowegis_control', 'fields.layers.load'],
                ['tl_cowegis_control', 'fields.layers.save'],
                ['tl_cowegis_control', 'fields.layers.eval.columnFields.layer.options'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\OptionsListener' => [
                ['tl_cowegis_map', 'fields.zoom.options'],
                ['tl_cowegis_map', 'fields.minZoom.options'],
                ['tl_cowegis_map', 'fields.maxZoom.options'],
                ['tl_cowegis_map', 'fields.locateMaxZoom.options'],
                ['tl_cowegis_layer', 'fields.minZoom.options'],
                ['tl_cowegis_layer', 'fields.maxZoom.options'],
                ['tl_cowegis_layer', 'fields.maxNativeZoom.options'],
                ['tl_cowegis_layer', 'fields.disableClusteringAtZoom.options'],
                ['tl_cowegis_control', 'fields.zoomControl.options'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\StyleDcaListener' => [
                ['tl_cowegis_style', 'fields.type.options'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\AliasGenerator' => [
                ['tl_cowegis_map', 'fields.alias.save'],
                ['tl_cowegis_marker', 'fields.alias.save'],
                ['tl_cowegis_layer', 'fields.alias.save'],
                ['tl_cowegis_icon', 'fields.alias.save'],
                ['tl_cowegis_popup', 'fields.alias.save'],
                ['tl_cowegis_control', 'fields.alias.save'],
                ['tl_cowegis_style', 'fields.alias.save'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\Validator' => [
                ['tl_cowegis_marker', 'fields.coordinates.save'],
                ['tl_cowegis_map', 'fields.center.save'],
                ['tl_cowegis_popup', 'fields.offset.save'],
                ['tl_cowegis_map', 'fields.alias.save'],
                ['tl_cowegis_marker', 'fields.alias.save'],
                ['tl_cowegis_layer', 'fields.alias.save'],
                ['tl_cowegis_icon', 'fields.alias.save'],
                ['tl_cowegis_popup', 'fields.alias.save'],
                ['tl_cowegis_control', 'fields.alias.save'],
                ['tl_cowegis_style', 'fields.alias.save'],
            ],
        ];
    }

    /** @param list<array{string, string}> $expected */
    #[DataProvider('callbackClassProvider')]
    public function testCallbackAttributesMatchGoldenList(string $class, array $expected): void
    {
        if (
            ! in_array($class, [
                'Cowegis\Bundle\Contao\EventListener\Dca\ModuleDcaListener',
                'Cowegis\Bundle\Contao\EventListener\Dca\StyleDcaListener',
            ], true)
        ) {
            self::markTestIncomplete('AsCallback attributes added in Task 14/15');
        }

        $actual = [];
        /** @psalm-suppress ArgumentTypeCoercion golden-list keys are class names */
        $reflection = new ReflectionClass($class);
        foreach ($reflection->getMethods() as $method) {
            foreach ($method->getAttributes(AsCallback::class) as $attribute) {
                $instance = $attribute->newInstance();
                $actual[] = $instance->table . '::' . $instance->target;
            }
        }

        $expectedFlat = array_map(static fn (array $pair): string => $pair[0] . '::' . $pair[1], $expected);

        sort($actual);
        sort($expectedFlat);

        self::assertSame($expectedFlat, $actual, $class);
    }

    /** @return iterable<string, array{string, list<array{string, string}>}> */
    public static function callbackClassProvider(): iterable
    {
        foreach (self::goldenCallbacks() as $class => $pairs) {
            yield $class => [$class, $pairs];
        }
    }

    public function testEventListenerAttributes(): void
    {
        $expected = [
            BackendMenuListener::class => 'contao.backend_menu_build',
            BackendStyleListener::class => null,
            LayerResponseListener::class => 'Cowegis\Bundle\Api\Event\LayerResponseEvent',
            MapResponseListener::class => 'Cowegis\Bundle\Api\Event\MapResponseEvent',
            ApplyFilterRuleMarkerListener::class => 'Cowegis\Bundle\Contao\Event\ApplyFilterRuleEvent',
        ];

        foreach ($expected as $class => $event) {
            $attributes = (new ReflectionClass($class))->getAttributes(AsEventListener::class);
            self::assertCount(1, $attributes, $class);
            self::assertSame($event, $attributes[0]->newInstance()->event, $class);
        }
    }
}
