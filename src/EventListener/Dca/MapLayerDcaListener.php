<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use Contao\BackendTemplate;
use Contao\DataContainer;
use Contao\Input;
use Cowegis\Bundle\Contao\Map\Layer\LayerTypeRegistry;
use Cowegis\Bundle\Contao\Model\LayerRepository;
use Cowegis\Bundle\Contao\Model\Map\MapLayerModel;
use Cowegis\Bundle\Contao\Model\Map\MapLayerRepository;
use Cowegis\Bundle\Contao\Model\Map\MapPaneRepository;
use Cowegis\Core\Filter\FilterFactory;
use Netzmacht\Contao\Toolkit\Dca\Listener\AbstractListener;
use Netzmacht\Contao\Toolkit\Dca\Manager;
use Netzmacht\Contao\Toolkit\Dca\Options\OptionsBuilder;

use function iterator_to_array;

final class MapLayerDcaListener extends AbstractListener
{
    /** @var string */
    protected static $name = 'tl_cowegis_map_layer';

    /** @var MapPaneRepository */
    private $paneRepository;

    /**
     * @var FilterFactory
     */
    private $filterFactory;

    /**
     * @var LayerTypeRegistry
     */
    private $layerTypes;
    /**
     * @var LayerRepository
     */
    private $layerRepository;
    /**
     * @var MapLayerRepository
     */
    private $mapLayerRepository;

    public function __construct(
        Manager $dcaManager,
        LayerTypeRegistry $layerTypes,
        MapLayerRepository $mapLayerRepository,
        LayerRepository $layerRepository,
        MapPaneRepository $paneRepository,
        FilterFactory $filterFactory
    ) {
        parent::__construct($dcaManager);

        $this->layerTypes         = $layerTypes;
        $this->paneRepository     = $paneRepository;
        $this->filterFactory      = $filterFactory;
        $this->layerRepository    = $layerRepository;
        $this->mapLayerRepository = $mapLayerRepository;
    }

    public function initializePalette(DataContainer $dataContainer): void
    {
        // TODO: Multi edit support
        if (Input::get('act') !== 'edit') {
            return;
        }

        $mapLayer = $this->mapLayerRepository->find((int) $dataContainer->id);
        if (! $mapLayer instanceof MapLayerModel) {
            return;
        }

        $definition = $this->getDefinition();
        $layerModel = $mapLayer->layerModel();
        if ($definition->has(['palettes', $layerModel->type])) {
            $definition->set(['palettes', 'default'], $definition->get(['palettes', $layerModel->type]));
        }

        $this->getDefinition()->modify(
            ['palettes', 'default'],
            static function (string $palette) {
                return 'layerId;' . $palette;
            }
        );
    }

    /** @param array<string,mixed> $row */
    public function rowLabel(array $row): string
    {
        return $this->getFormatter()->formatValue('layerId', $row['layerId']);
    }

    public function layerFieldLabel(DataContainer $dataContainer): string
    {
        $layerModel = $this->layerRepository->find((int) $dataContainer->activeRecord->layerId);
        $label      = $this->rowLabel($dataContainer->activeRecord->row());
        $layerType  = null;

        if ($layerModel && $this->layerTypes->has($layerModel->type)) {
            $layerType = $this->layerTypes->get($layerModel->type);
            $label     = $layerType->label($label, $layerModel->row());
        }

        $template = new BackendTemplate('be_cowegis_map_layer_label');
        $template->setData(
            [
                'row' => $dataContainer->activeRecord,
                'label' => $label,
                'type'  => $layerType,
            ]
        );

        return $template->parse();
    }

    /** @return array<string,string> */
    public function paneOptions(DataContainer $dataContainer): array
    {
        if ($dataContainer->activeRecord) {
            $collection = $this->paneRepository->findByMap((int) $dataContainer->activeRecord->pid);

            return OptionsBuilder::fromCollection($collection, 'title')->getOptions();
        }

        return [];
    }

    /** @return array<int,string> */
    public function fileRuleOptions(): array
    {
        return iterator_to_array($this->filterFactory->ruleNames());
    }
}
