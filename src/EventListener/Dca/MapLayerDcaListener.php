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

use function assert;
use function is_string;
use function iterator_to_array;

final class MapLayerDcaListener extends AbstractListener
{
    /** @var string */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected static $name = 'tl_cowegis_map_layer';

    public function __construct(
        Manager $dcaManager,
        private readonly LayerTypeRegistry $layerTypes,
        private readonly MapLayerRepository $mapLayerRepository,
        private readonly LayerRepository $layerRepository,
        private readonly MapPaneRepository $paneRepository,
        private readonly FilterFactory $filterFactory,
    ) {
        parent::__construct($dcaManager);
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
            },
        );
    }

    /** @param array<string,mixed> $row */
    public function rowLabel(array $row, DataContainer $dataContainer): string
    {
        $formatted = $this->getFormatter()->formatValue('layerId', $row['layerId'], $dataContainer);
        assert(is_string($formatted) || $formatted === null);

        return (string) $formatted;
    }

    public function layerFieldLabel(DataContainer $dataContainer): string
    {
        if (! $dataContainer->activeRecord) {
            return (string) $dataContainer->id;
        }

        $layerModel = $this->layerRepository->find((int) $dataContainer->activeRecord->layerId);
        $label      = $this->rowLabel($dataContainer->activeRecord->row(), $dataContainer);
        $layerType  = null;

        if ($layerModel && $this->layerTypes->has($layerModel->type)) {
            $layerType = $this->layerTypes->get($layerModel->type);
            $label     = $layerType->label($label, $layerModel->row());
        }

        $template = new BackendTemplate('be_cowegis_map_layer_label');
        $template->setData(
            [
                'row'   => $dataContainer->activeRecord,
                'label' => $label,
                'type'  => $layerType,
            ],
        );

        return $template->parse();
    }

    /** @return array<string, array<string, string>|string> */
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
