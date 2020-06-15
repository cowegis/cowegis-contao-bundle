<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\File;

use Contao\FilesModel;
use Cowegis\Bundle\Contao\Hydrator\Layer\LayerTypeHydrator;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\Map\MapLayerModel;
use Cowegis\Core\Definition\GeoJson\ExternalData;
use Cowegis\Core\Definition\Expression\InlineExpression;
use Cowegis\Core\Definition\Layer\DataLayer;
use Cowegis\Core\Definition\Layer\Layer;
use Cowegis\Core\Provider\Context;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;

final class FileLayerHydrator extends LayerTypeHydrator
{
    /**
     * @var RepositoryManager
     */
    private $repositoryManager;

    public function __construct(RepositoryManager $repositoryManager)
    {
        $this->repositoryManager = $repositoryManager;
    }

    protected function supportedType() : string
    {
        return 'file';
    }

    protected function hydrateLayer(LayerModel $layerModel, Layer $layer, MapLayerContext $context) : void
    {
        assert($layer instanceof DataLayer);

        if ($layerModel->pointToLayer) {
            $layer->options()->set(
                'pointToLayer',
                $context->callbacks()->add(new InlineExpression($layerModel->pointToLayer))
            );
        }

        $repository = $this->repositoryManager->getRepository(FilesModel::class);
        $fileModel = $repository->findOneBy(['.uuid=?'], [$layerModel->file]);

        if (! $fileModel instanceof FilesModel || $fileModel->type !== 'file') {
            return;
        }

        $layer->withData(new ExternalData($fileModel->path, $layerModel->fileFormat));
    }
}
