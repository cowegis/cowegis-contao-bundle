<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Test\Schema;

use Cowegis\Bundle\Contao\Schema\LayersSchemaDescriber;
use Cowegis\Core\Schema\Id\IntegerIdSchema;
use Cowegis\Core\Schema\SchemaBuilder;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Info;
use PHPUnit\Framework\TestCase;

use function array_column;

final class LayersSchemaDescriberTest extends TestCase
{
    /** @return array<string, mixed> */
    private static function build(): array
    {
        $builder = SchemaBuilder::create(
            Info::create()->title('t')->version('1'),
            new IntegerIdSchema(),
        );

        (new LayersSchemaDescriber())->describe($builder);

        return $builder->build()->toArray();
    }

    public function testDescribesBothDataPathsWithMapIdParam(): void
    {
        $paths = self::build()['paths'];

        self::assertArrayHasKey('/map/{mapId}/data/{layerId}', $paths);
        self::assertArrayHasKey('/map/{mapId}/vectors/{layerId}', $paths);
        self::assertArrayNotHasKey('/map/{definitionId}/data/{layerId}', $paths);

        $params = array_column($paths['/map/{mapId}/data/{layerId}']['get']['parameters'], 'name');
        self::assertSame(['mapId', 'layerId'], $params);
    }

    public function testWrapsDataInAnAssetsEnvelopeAndDefinesA404(): void
    {
        $doc = self::build();

        self::assertArrayHasKey('LayerDataResponse', $doc['components']['schemas']);
        $props = $doc['components']['schemas']['LayerDataResponse']['properties'];
        self::assertArrayHasKey('data', $props);
        self::assertArrayHasKey('assets', $props);

        $responses = $doc['paths']['/map/{mapId}/data/{layerId}']['get']['responses'];
        self::assertArrayHasKey(200, $responses);
        self::assertArrayHasKey(404, $responses);
    }
}
