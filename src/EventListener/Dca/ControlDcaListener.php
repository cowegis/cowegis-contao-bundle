<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\StringUtil;
use Cowegis\Bundle\Contao\Map\Control\ControlTypeRegistry;
use Cowegis\Bundle\Contao\Model\LayerRepository;
use Cowegis\ContaoGeocoder\Provider\Geocoder;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Netzmacht\Contao\Toolkit\Dca\Listener\AbstractListener;
use Netzmacht\Contao\Toolkit\Dca\Manager as DcaManager;

use function array_map;
use function defined;
use function time;

final class ControlDcaListener extends AbstractListener
{
    public function __construct(
        DcaManager $dcaManager,
        private readonly ControlTypeRegistry $controlTypes,
        private readonly Connection $connection,
        private LayerRepository $layerRepository,
        private Geocoder|null $geocoder,
    ) {
        parent::__construct($dcaManager);
    }

    public static function getName(): string
    {
        return 'tl_cowegis_control';
    }

    /** @param array<string,mixed> $row */
    public function rowLabel(array $row): string
    {
        return $row['title'];
    }

    /** @return string[] */
    public function typeOptions(): array
    {
        $options = [];
        foreach ($this->controlTypes as $controlType) {
            $options[] = $controlType->name();
        }

        return $options;
    }

    /** @return array<int,string> */
    public function layerOptions(): array
    {
        // We can't trust the MCW https://github.com/menatwork/contao-multicolumnwizard-bundle/issues/66
        if (! defined('CURRENT_ID')) {
            return [];
        }

        $result = $this->connection->executeQuery(
            'SELECT layerId from tl_cowegis_map_layer WHERE pid=:mapId AND active=\'1\'',
            ['mapId' => CURRENT_ID],
        );

        $collection = $this->layerRepository->findMultipleByIds($result->fetchFirstColumn(), ['order' => 'sorting']);

        $options = [];
        foreach ($collection ?: [] as $model) {
            $options[$model->id()] = $model->title;
        }

        return $options;
    }

    /**
     * Load layer relations.
     *
     * @param mixed         $value         The actual value.
     * @param DataContainer $dataContainer The data container driver.
     *
     * @return array<int, array<string, mixed>>
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function loadLayerRelations(mixed $value, DataContainer $dataContainer): array
    {
        $query  = 'SELECT lid As layer, mode FROM tl_cowegis_control_layer WHERE cid=:cid ORDER BY sorting';
        $result = $this->connection->executeQuery($query, ['cid' => $dataContainer->id]);

        return $result->fetchAllAssociative();
    }

    /**
     * Save layer relations.
     *
     * @param mixed         $layers        The layer id values.
     * @param DataContainer $dataContainer The dataContainer driver.
     */
    public function saveLayerRelations(mixed $layers, DataContainer $dataContainer): null
    {
        $new       = StringUtil::deserialize($layers, true);
        $values    = [];
        $query     = 'SELECT * FROM tl_cowegis_control_layer WHERE cid=:cid order BY sorting';
        $statement = $this->connection->executeQuery($query, ['cid' => $dataContainer->id]);

        while ($row = $statement->fetchAssociative()) {
            $values[$row['lid']] = $row;
        }

        $sorting = 0;

        foreach ($new as $layer) {
            if (! isset($values[$layer['layer']])) {
                $data = [
                    'tstamp'  => time(),
                    'lid'     => $layer['layer'],
                    'cid'     => $dataContainer->id,
                    'mode'    => $layer['mode'],
                    'sorting' => $sorting,
                ];

                $this->connection->insert('tl_cowegis_control_layer', $data);
                $sorting += 128;
            } else {
                $this->connection->update(
                    'tl_cowegis_control_layer',
                    [
                        'tstamp'  => time(),
                        'sorting' => $sorting,
                        'mode'    => $layer['mode'],
                    ],
                    [
                        'id' => $values[$layer['layer']]['id'],
                    ],
                );

                $sorting += 128;
                unset($values[$layer['layer']]);
            }
        }

        $ids = array_map(
            /** @param array<string,mixed> $item */
            static function (array $item): int {
                return (int) $item['id'];
            },
            $values,
        );

        if ($ids) {
            $this->connection->executeStatement(
                'DELETE FROM tl_cowegis_control_layer WHERE id IN(?)',
                [$ids],
                [ArrayParameterType::INTEGER],
            );
        }

        return null;
    }

    /** @Callback(table="tl_cowegis_control", target="config.onload") */
    public function initializeGeocoderPalette(): void
    {
        if (! $this->geocoder instanceof Geocoder) {
            return;
        }

        PaletteManipulator::create()
            ->addField('geocoder', 'config_legend', PaletteManipulator::POSITION_PREPEND)
            ->applyToPalette('geocoder', 'tl_cowegis_control');
    }

    /**
     * @return array<string,string>
     *
     * @Callback(table="tl_cowegis_control", target="fields.geocoder.options")
     */
    public function geocoderOptions(): array
    {
        $options = [];

        if (! $this->geocoder instanceof Geocoder) {
            return $options;
        }

        foreach ($this->geocoder as $provider) {
            $options[$provider->providerId()] = $provider->title();
        }

        return $options;
    }
}
