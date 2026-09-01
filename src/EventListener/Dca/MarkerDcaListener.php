<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Netzmacht\Contao\Toolkit\Dca\DcaManager;
use Netzmacht\Contao\Toolkit\Dca\Listener\AbstractListener;
use Override;

use function array_keys;
use function count;

final class MarkerDcaListener extends AbstractListener
{
    public function __construct(DcaManager $dcaManager, private readonly Connection $connection)
    {
        parent::__construct($dcaManager);
    }

    #[Override]
    public static function getName(): string
    {
        return 'tl_cowegis_marker';
    }

    /**
     * Generate the row label.
     *
     * @param array<string,mixed> $row Current data row.
     */
    #[AsCallback('tl_cowegis_marker', 'list.sorting.child_record')]
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
    #[AsCallback('tl_cowegis_marker', 'fields.coordinates.save')]
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
    #[AsCallback('tl_cowegis_marker', 'fields.coordinates.load', priority: 128)]
    public function loadCoordinates(string|null $value, DataContainer $dataContainer): string|null
    {
        $query  = 'SELECT latitude, longitude, altitude FROM tl_cowegis_marker WHERE id=:id';
        $result = $this->connection->executeQuery($query, ['id' => $dataContainer->id]);
        $row    = $result->fetchAssociative();

        if ($row !== false) {
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
