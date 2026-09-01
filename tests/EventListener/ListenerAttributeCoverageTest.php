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
use function sort;

final class ListenerAttributeCoverageTest extends TestCase
{
    /** @return array<string, list<array{string, string, int|null}>> table+target+priority triples */
    private static function goldenCallbacks(): array
    {
        return [
            'Cowegis\Bundle\Contao\EventListener\Dca\ContentDcaListener' => [
                ['tl_content', 'config.onload', null],
                ['tl_content', 'fields.cowegis_client.options', null],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\ModuleDcaListener' => [
                ['tl_module', 'config.onload', null],
                ['tl_module', 'fields.cowegis_client.options', null],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\LayerDcaListener' => [
                ['tl_cowegis_layer', 'fields.type.options', null],
                ['tl_cowegis_layer', 'fields.fileFormat.options', null],
                ['tl_cowegis_layer', 'fields.amenityIcons.eval.columnFields.amenity.options', null],
                ['tl_cowegis_layer', 'fields.file.load', null],
                ['tl_cowegis_layer', 'list.label.label', null],
                ['tl_cowegis_layer', 'list.operations.data.button', null],
                ['tl_cowegis_layer', 'list.sorting.paste_button', null],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\MapLayerSelectionDcaListener' => [
                ['tl_cowegis_layer', 'config.onload', null],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\MapDcaListener' => [
                ['tl_cowegis_map', 'fields.layers.eval.listCallback', null],
                ['tl_cowegis_map', 'config.onload', null],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\MapLayerDcaListener' => [
                ['tl_cowegis_map_layer', 'config.onload', null],
                ['tl_cowegis_map_layer', 'list.sorting.child_record', null],
                ['tl_cowegis_map_layer', 'fields.pane.options', null],
                ['tl_cowegis_map_layer', 'fields.dataPane.options', null],
                ['tl_cowegis_map_layer', 'fields.filterRules.options', null],
                ['tl_cowegis_map_layer', 'fields.layerId.input_field', null],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\MapPaneDcaListener' => [
                ['tl_cowegis_map_pane', 'list.sorting.child_record', null],
                ['tl_cowegis_map_pane', 'fields.name.save', null],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\MarkerDcaListener' => [
                ['tl_cowegis_marker', 'list.sorting.child_record', null],
                ['tl_cowegis_marker', 'fields.coordinates.save', null],
                ['tl_cowegis_marker', 'fields.coordinates.load', 128],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\IconDcaListener' => [
                ['tl_cowegis_icon', 'fields.type.options', null],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\ControlDcaListener' => [
                ['tl_cowegis_control', 'list.sorting.child_record', null],
                ['tl_cowegis_control', 'fields.type.options', null],
                ['tl_cowegis_control', 'fields.layers.load', null],
                ['tl_cowegis_control', 'fields.layers.save', null],
                ['tl_cowegis_control', 'fields.layers.eval.columnFields.layer.options', null],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\OptionsListener' => [
                ['tl_cowegis_map', 'fields.zoom.options', null],
                ['tl_cowegis_map', 'fields.minZoom.options', null],
                ['tl_cowegis_map', 'fields.maxZoom.options', null],
                ['tl_cowegis_map', 'fields.locateMaxZoom.options', null],
                ['tl_cowegis_layer', 'fields.minZoom.options', null],
                ['tl_cowegis_layer', 'fields.maxZoom.options', null],
                ['tl_cowegis_layer', 'fields.maxNativeZoom.options', null],
                ['tl_cowegis_layer', 'fields.disableClusteringAtZoom.options', null],
                ['tl_cowegis_control', 'fields.zoomControl.options', null],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\StyleDcaListener' => [
                ['tl_cowegis_style', 'fields.type.options', null],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\AliasGenerator' => [
                ['tl_cowegis_map', 'fields.alias.save', 128],
                ['tl_cowegis_marker', 'fields.alias.save', 128],
                ['tl_cowegis_layer', 'fields.alias.save', 128],
                ['tl_cowegis_icon', 'fields.alias.save', 128],
                ['tl_cowegis_popup', 'fields.alias.save', 128],
                ['tl_cowegis_control', 'fields.alias.save', 128],
                ['tl_cowegis_style', 'fields.alias.save', 128],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\Validator' => [
                ['tl_cowegis_marker', 'fields.coordinates.save', 128],
                ['tl_cowegis_map', 'fields.center.save', 128],
                ['tl_cowegis_popup', 'fields.offset.save', 128],
                ['tl_cowegis_map', 'fields.alias.save', null],
                ['tl_cowegis_marker', 'fields.alias.save', null],
                ['tl_cowegis_layer', 'fields.alias.save', null],
                ['tl_cowegis_icon', 'fields.alias.save', null],
                ['tl_cowegis_popup', 'fields.alias.save', null],
                ['tl_cowegis_control', 'fields.alias.save', null],
                ['tl_cowegis_style', 'fields.alias.save', null],
            ],
        ];
    }

    /** @param list<array{string, string, int|null}> $expected */
    #[DataProvider('callbackClassProvider')]
    public function testCallbackAttributesMatchGoldenList(string $class, array $expected): void
    {
        $actual = [];
        /** @psalm-suppress ArgumentTypeCoercion golden-list keys are class names */
        $reflection = new ReflectionClass($class);
        foreach ($reflection->getMethods() as $method) {
            foreach ($method->getAttributes(AsCallback::class) as $attribute) {
                $instance = $attribute->newInstance();
                $actual[] = $instance->table . '::' . $instance->target . '::' . ($instance->priority ?? 'null');
            }
        }

        $expectedFlat = array_map(
            static fn (array $triple): string => $triple[0] . '::' . $triple[1] . '::' . ($triple[2] ?? 'null'),
            $expected,
        );

        sort($actual);
        sort($expectedFlat);

        self::assertSame($expectedFlat, $actual, $class);
    }

    /** @return iterable<string, array{string, list<array{string, string, int|null}>}> */
    public static function callbackClassProvider(): iterable
    {
        foreach (self::goldenCallbacks() as $class => $triples) {
            yield $class => [$class, $triples];
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
