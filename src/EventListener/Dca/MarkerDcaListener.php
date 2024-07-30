<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use Contao\DataContainer;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Netzmacht\Contao\Toolkit\Dca\Listener\AbstractListener;
use Netzmacht\Contao\Toolkit\Dca\Manager;

use function array_keys;
use function count;

final class MarkerDcaListener extends AbstractListener
{
    public function __construct(Manager $dcaManager, private readonly Connection $connection)
    {
        parent::__construct($dcaManager);
    }

    public static function getName(): string
    {
        return 'tl_cowegis_marker';
    }

    /**
     * Generate the row label.
     *
     * @param array<string,mixed> $row Current data row.
     */
    public function rowLabel(array $row): string
    {
        return $row['title'];
    }

    /**
     * Save the coordinates.
     *
     * @param string        $value         The raw data.
     * @param DataContainer $dataContainer The data container driver.
     */
    public function saveCoordinates(string $value, DataContainer $dataContainer): void
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
     * @param string|null   $value         The raw data.
     * @param DataContainer $dataContainer The data container driver.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function loadCoordinates(string|null $value, DataContainer $dataContainer): string|null
    {
        $query  = 'SELECT latitude, longitude, altitude FROM tl_cowegis_marker WHERE id=:id';
        $result = $this->connection->executeQuery($query, ['id' => $dataContainer->id]);
        $row    = $result->fetchAssociative();

        if ($row) {
            $buffer = $row['latitude'];

            if (! $buffer || ! $row['longitude']) {
                return $buffer;
            }

            $buffer .= ',' . $row['longitude'];

            if ($buffer && $row['altitude']) {
                $buffer .= ',' . $row['altitude'];
            }

            return $buffer;
        }

        return '';
    }
}
