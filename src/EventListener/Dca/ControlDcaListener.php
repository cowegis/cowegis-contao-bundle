<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use Contao\Input;
use Contao\StringUtil;
use Cowegis\Bundle\Contao\Map\Control\ControlTypeRegistry;
use Cowegis\Bundle\Contao\Model\LayerRepository;
use Doctrine\DBAL\Connection;
use Netzmacht\Contao\Leaflet\Model\LayerModel;
use Netzmacht\Contao\Toolkit\Dca\Listener\AbstractListener;
use Netzmacht\Contao\Toolkit\Dca\Manager as DcaManager;
use Netzmacht\Contao\Toolkit\Dca\Options\OptionsBuilder;
use PDO;
use function array_map;
use function defined;
use function time;

final class ControlDcaListener extends AbstractListener
{
    protected static $name = 'tl_cowegis_control';

    /** @var ControlTypeRegistry */
    private $controlTypes;

    /** @var LayerRepository */
    private $layerRepository;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(
        DcaManager $dcaManager,
        ControlTypeRegistry $controlTypes,
        Connection $connection,
        LayerRepository $layerRepository
    ) {
        parent::__construct($dcaManager);

        $this->controlTypes    = $controlTypes;
        $this->connection      = $connection;
        $this->layerRepository = $layerRepository;
    }

    public function rowLabel(array $row) : string
    {
        return $row['title'];
    }

    public function typeOptions() : array
    {
        $options = [];
        foreach ($this->controlTypes as $controlType) {
            $options[] = $controlType->name();
        }

        return $options;
    }

    public function layerOptions() : array
    {
        // We can't trust the MCW https://github.com/menatwork/contao-multicolumnwizard-bundle/issues/66
        if (!defined('CURRENT_ID')) {
            return [];
        }

        $statement = $this->connection->prepare(
            'SELECT layerId from tl_cowegis_map_layer WHERE pid=:mapId AND active=\'1\''
        );
        $statement->bindValue('mapId', CURRENT_ID);
        $statement->execute();

        $collection = $this->layerRepository->findMultipleByIds(
            $statement->fetchAll(PDO::FETCH_COLUMN),
            ['order' => 'sorting']
        );

        $options = [];
        foreach ($collection ?: [] as $model) {
            $options[$model->id()] = $model->title;
        }

        return $options;
    }

    /**
     * Load layer relations.
     *
     * @param mixed          $value         The actual value.
     * @param \DataContainer $dataContainer The data container driver.
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function loadLayerRelations($value, $dataContainer)
    {
        $query     = 'SELECT lid As layer, mode FROM tl_cowegis_control_layer WHERE cid=:cid ORDER BY sorting';
        $statement = $this->connection->prepare($query);

        $statement->bindValue('cid', $dataContainer->id);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Save layer relations.
     *
     * @param mixed          $layers        The layer id values.
     * @param \DataContainer $dataContainer The dataContainer driver.
     *
     * @return null
     */
    public function saveLayerRelations($layers, $dataContainer)
    {
        $new       = StringUtil::deserialize($layers, true);
        $values    = [];
        $query     = 'SELECT * FROM tl_cowegis_control_layer WHERE cid=:cid order BY sorting';
        $statement = $this->connection->prepare($query);
        $statement->bindValue('cid', $dataContainer->id);
        $statement->execute();

        while ($row = $statement->fetch()) {
            $values[$row['lid']] = $row;
        }

        $sorting = 0;

        foreach ($new as $layer) {
            if (!isset($values[$layer['layer']])) {
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
                    ]
                );

                $sorting += 128;
                unset($values[$layer['layer']]);
            }
        }

        $ids = array_map(
            function ($item) {
                return $item['id'];
            },
            $values
        );

        if ($ids) {
            $this->connection->executeUpdate(
                'DELETE FROM tl_cowegis_control_layer WHERE id IN(?)',
                [$ids],
                [Connection::PARAM_INT_ARRAY]
            );
        }

        return null;
    }
}