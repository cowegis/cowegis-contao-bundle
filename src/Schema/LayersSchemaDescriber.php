<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Schema;

use Cowegis\Core\Schema\AssetSchema;
use Cowegis\Core\Schema\Error\ProblemResponses;
use Cowegis\Core\Schema\GeoData\GeoDataSchema;
use Cowegis\Core\Schema\SchemaBuilder;
use Cowegis\Core\Schema\SchemaDescriber;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Tag;
use Override;

final class LayersSchemaDescriber implements SchemaDescriber
{
    private const array DATA_TYPES = ['data', 'vectors'];

    #[Override]
    public function describe(SchemaBuilder $builder): void
    {
        $envelope = $builder->components()->withSchema(
            Schema::object('LayerDataResponse')
                ->description('Deferred layer data plus the assets required to render it')
                ->required('data', 'assets')
                ->properties(
                    Schema::ref(GeoDataSchema::FULL_REF, 'data'),
                    Schema::array('assets')->items(Schema::ref(AssetSchema::FULL_REF)),
                ),
        );

        $response = Response::ok('Deferred layer data')
            ->content(MediaType::json()->schema($envelope));

        $tag = Tag::create()->name('Layer data');

        foreach (self::DATA_TYPES as $type) {
            $operation = Operation::get()
                ->description('Deferred feature data for a single layer of a map')
                ->summary('Show layer data')
                ->parameters(
                    Parameter::path()->name('mapId')->schema($builder->idSchemaRef())->required(),
                    Parameter::path()->name('layerId')->schema($builder->idSchemaRef())->required(),
                )
                ->tags($tag)
                ->responses($response, ProblemResponses::notFound());

            $builder->withPathItem(
                (new PathItem())
                    ->route('/map/{mapId}/' . $type . '/{layerId}')
                    ->operations($operation),
            );
        }
    }
}
