<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\File;

use Contao\FilesModel;
use Cowegis\Bundle\Contao\Map\Layer\LayerType;
use Cowegis\Bundle\Contao\Map\Layer\MapLayerType;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\Map\MapLayerModel;
use Cowegis\Core\Definition\Layer\DataLayer;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;
use Override;

use function assert;

final class FileLayerType implements LayerType
{
    use MapLayerType;

    /** @param RepositoryManager $repositoryManager Repository manager. */
    public function __construct(private readonly RepositoryManager $repositoryManager)
    {
    }

    #[Override]
    public function name(): string
    {
        return 'file';
    }

    /** {@inheritDoc} */
    #[Override]
    public function label(string $label, array $row): string
    {
        $repository = $this->repositoryManager->getRepository(FilesModel::class);
        assert($repository instanceof ContaoRepository);
        /** @psalm-suppress UndefinedMagicMethod */
        $file = $repository->findByUuid($row['file']);

        if ($file) {
            $label .= ' <span class="tl_gray">(' . $file->path . ')</span>';
        }

        return $label;
    }

    #[Override]
    public function createDefinition(LayerModel $layerModel, MapLayerModel $mapLayerModel): DataLayer
    {
        return new DataLayer(
            $mapLayerModel->layerId(),
            $this->hydrateName($layerModel, $mapLayerModel),
            $this->hydrateInitialVisible($mapLayerModel),
        );
    }
}
