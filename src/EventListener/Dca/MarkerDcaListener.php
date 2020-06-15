<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use Contao\DataContainer;
use Contao\Input;
use Contao\StringUtil;
use Cowegis\Bundle\Contao\Model\LayerRepository;
use Doctrine\DBAL\Connection;
use Netzmacht\Contao\Toolkit\Dca\Listener\AbstractListener;
use Netzmacht\Contao\Toolkit\Dca\Manager;
use function array_keys;

final class MarkerDcaListener extends AbstractListener
{
    /** @var string */
    protected static $name = 'tl_cowegis_marker';

    /** @var Connection */
    private $connection;

    /** @var LayerRepository */
    private $layerRepository;

    public function __construct(Manager $dcaManager, Connection $connection, LayerRepository $layerRepository)
    {
        parent::__construct($dcaManager);

        $this->connection      = $connection;
        $this->layerRepository = $layerRepository;
    }

    /**
     * Generate the row label.
     *
     * @param array $row Current data row.
     *
     * @return string
     */
    public function rowLabel(array $row) : string
    {
        return $row['title'];
    }

    /**
     * Save the coordinates.
     *
     * @param string         $value         The raw data.
     * @param \DataContainer $dataContainer The data container driver.
     */
    public function saveCoordinates($value, $dataContainer) : void
    {
        $combined = [
            'latitude'  => null,
            'longitude' => null,
            'altitude'  => null,
        ];

        $values = StringUtil::trimsplit(',', $value);
        $keys   = array_keys($combined);
        $count  = count($values);

        if ($count >= 2 && $count <= 3) {
            for ($i = 0; $i < $count; $i++) {
                $combined[$keys[$i]] = $values[$i];
            }
        }

        $this->connection->update('tl_cowegis_marker', $combined, ['id' => $dataContainer->id]);
    }

    /**
     * Load the coordinates.
     *
     * @param string         $value         The raw data.
     * @param \DataContainer $dataContainer The data container driver.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function loadCoordinates($value, $dataContainer) : ?string
    {
        $query     = 'SELECT latitude, longitude, altitude FROM tl_cowegis_marker WHERE id=:id';
        $statement = $this->connection->prepare($query);
        $statement->bindValue('id', $dataContainer->id);

        $statement->execute();

        if ($row = $statement->fetch()) {
            $buffer = $row['latitude'];

            if ($buffer && $row['longitude']) {
                $buffer .= ',' . $row['longitude'];
            } else {
                return $buffer;
            }

            if ($buffer && $row['altitude']) {
                $buffer .= ',' . $row['altitude'];
            }

            return $buffer;
        }

        return '';
    }
}
