<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\File;

use Contao\FilesModel;
use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Map\Layer\LayerTypeHydrator;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Core\Definition\Expression\InlineExpression;
use Cowegis\Core\Definition\GeoData\ExternalData;
use Cowegis\Core\Definition\Layer\DataLayer;
use Cowegis\Core\Definition\Layer\Layer;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;
use Netzmacht\Contao\Toolkit\Response\ResponseTagger;

use function assert;

final class FileLayerHydrator extends LayerTypeHydrator
{
    public function __construct(private readonly RepositoryManager $repositoryManager, ResponseTagger $responseTagger)
    {
        parent::__construct($responseTagger);
    }

    protected function supportedType(): string
    {
        return 'file';
    }

    protected function hydrateLayer(
        LayerModel $layerModel,
        Layer $layer,
        MapLayerContext $context,
        Hydrator $hydrator,
    ): void {
        assert($layer instanceof DataLayer);

        if ($layerModel->pointToLayer) {
            $layer->options()->set(
                'pointToLayer',
                $context->callbacks()->add(new InlineExpression($layerModel->pointToLayer)),
            );
        }

        $repository = $this->repositoryManager->getRepository(FilesModel::class);
        $fileModel  = $repository->findOneBy(['.uuid=?'], [$layerModel->file]);

        if (! $fileModel instanceof FilesModel || $fileModel->type !== 'file') {
            return;
        }

        $layer->withData(new ExternalData($fileModel->path, $layerModel->fileFormat));
    }
}
