<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Markers;

use Cowegis\Bundle\Contao\Map\Layer\DataLayerType;
use Cowegis\Bundle\Contao\Map\Layer\MapLayerType;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\Map\MapLayerModel;
use Cowegis\Bundle\Contao\Model\MarkerRepository;
use Cowegis\Core\Definition\Layer\DataLayer;
use Cowegis\Core\Definition\Layer\Layer;
use Symfony\Contracts\Translation\TranslatorInterface;

use function sprintf;

final class MarkersLayerType implements DataLayerType
{
    use MapLayerType;

    /** @var MarkerRepository */
    private $markerRepository;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(MarkerRepository $markerRepository, TranslatorInterface $translator)
    {
        $this->markerRepository = $markerRepository;
        $this->translator       = $translator;
    }

    public function name(): string
    {
        return 'markers';
    }

    public function dataTable(): string
    {
        return 'tl_cowegis_marker';
    }

    /** {@inheritDoc} */
    public function label(string $label, array $row): string
    {
        $count  = $this->markerRepository->countBy(['.pid=?'], [$row['id']]);
        $label .= sprintf(
            '<span class="tl_gray"> (%s %s)</span>',
            $count,
            $this->translator->trans('tl_cowegis_layer.countEntries', [], 'contao_tl_cowegis_layer')
        );

        return $label;
    }

    public function createDefinition(LayerModel $layerModel, MapLayerModel $mapLayerModel): Layer
    {
        return new DataLayer(
            $mapLayerModel->layerId(),
            $this->hydrateName($layerModel, $mapLayerModel),
            $this->hydrateInitialVisible($mapLayerModel)
        );
    }
}
