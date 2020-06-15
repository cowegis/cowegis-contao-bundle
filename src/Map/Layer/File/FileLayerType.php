<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\File;

use Contao\FilesModel;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\Map\MapLayerModel;
use Cowegis\Bundle\Contao\Map\Layer\LayerType;
use Cowegis\Bundle\Contao\Map\Layer\MapLayerType;
use Cowegis\Core\Definition\DefinitionId\IntegerDefinitionId;
use Cowegis\Core\Definition\Layer\DataLayer;
use Cowegis\Core\Definition\Layer\LayerId;
use Cowegis\Core\Definition\Layer\Layer;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;

final class FileLayerType implements LayerType
{
    use MapLayerType;

    /**
     * Repository manager.
     *
     * @var RepositoryManager
     */
    private $repositoryManager;

    /**
     * FileLabelRenderer constructor.
     *
     * @param RepositoryManager $repositoryManager Repository manager.
     */
    public function __construct(RepositoryManager $repositoryManager)
    {
        $this->repositoryManager = $repositoryManager;
    }

    public function name() : string
    {
        return 'file';
    }

    public function label(string $label, array $row) : string
    {
        $repository = $this->repositoryManager->getRepository(FilesModel::class);
        $file       = $repository->findByUuid($row['file']);

        if ($file) {
            $label .= ' <span class="tl_gray">(' . $file->path . ')</span>';
        }

        return $label;
    }

    public function createDefinition(LayerModel $layerModel, MapLayerModel $mapLayerModel) : Layer
    {
        return new DataLayer(
            $mapLayerModel->layerId(),
            $this->hydrateName($layerModel, $mapLayerModel),
            $this->hydrateInitialVisible($mapLayerModel)
        );
    }
}
