<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Schema;

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

final class LayersSchemaDescriber implements SchemaDescriber
{
    public function describe(SchemaBuilder $builder): void
    {
        $response = Response::ok('data')
            ->content(MediaType::json()->schema(Schema::ref(GeoDataSchema::FULL_REF)));

        // TODO error responses

        $layerDetails = Operation::get()
            ->description('')
            ->summary('Show full map details')
            ->parameters(
                Parameter::path()
                    ->name('definitionId')
                    ->schema($builder->idSchemaRef())
                    ->required(),
                Parameter::path()
                    ->name('layerId')
                    ->schema($builder->idSchemaRef())
                    ->required(),
            )
            ->tags(Tag::create()->name('Layer data'))
            ->responses($response);

        $builder->withPathItem(
            (new PathItem())
                ->route('/map/{definitionId}/data/{layerId}')
                ->operations($layerDetails),
        );
    }
}
