<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Reference;

use Cowegis\Bundle\Contao\Map\Layer\LayerType;
use Cowegis\Bundle\Contao\Map\Layer\LayerTypeRegistry;
use Cowegis\Bundle\Contao\Map\Layer\LayerTypeRegistryAware;
use Cowegis\Bundle\Contao\Map\Layer\MapLayerType;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\LayerRepository;
use Cowegis\Bundle\Contao\Model\Map\MapLayerModel;
use Cowegis\Core\Definition\DefinitionId\IntegerDefinitionId;
use Cowegis\Core\Definition\Layer\Layer;
use Cowegis\Core\Definition\Map\MapId;
use Cowegis\Core\Exception\LayerNotFound;
use RuntimeException;

final class ReferenceLayerType implements LayerType, LayerTypeRegistryAware
{
    use MapLayerType;

    private LayerTypeRegistry|null $layerTypes = null;

    public function __construct(private readonly LayerRepository $layerRepository)
    {
    }

    public function setRegistry(LayerTypeRegistry $layerTypes): void
    {
        $this->layerTypes = $layerTypes;
    }

    public function name(): string
    {
        return 'reference';
    }

    public function createDefinition(LayerModel $layerModel, MapLayerModel $mapLayerModel): Layer
    {
        if (! $this->layerTypes instanceof LayerTypeRegistry) {
            throw new RuntimeException('Layer registry has to be set.');
        }

        $referenceModel = $this->layerRepository->find((int) $layerModel->reference);
        if (! $referenceModel instanceof LayerModel) {
            throw LayerNotFound::withLayerId(
                $layerModel->layerId(),
                MapId::fromValue(IntegerDefinitionId::fromValue((int) $mapLayerModel->pid)),
            );
        }

        return $this->layerTypes->get($referenceModel->type)->createDefinition($referenceModel, $mapLayerModel);
    }

    /** {@inheritDoc} */
    public function label(string $label, array $row): string
    {
        $reference = $this->layerRepository->find((int) $row['reference']);

        if ($reference) {
            $label .= '<span class="tl_gray"> (' . $reference->title . ')</span>';
        }

        return $label;
    }
}
