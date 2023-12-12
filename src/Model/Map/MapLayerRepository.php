<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model\Map;

use Contao\Model\Collection;
use Doctrine\DBAL\Connection;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;

/** @extends ContaoRepository<MapLayerModel> */
final class MapLayerRepository extends ContaoRepository
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct(MapLayerModel::class);
    }

    /** @param array<string,mixed> $options */
    public function findActive(int $mapId, array $options = []): Collection|null
    {
        $options['sorting'] = $options['sorting'] ?? '.sorting';

        return $this->findBy(['.pid=?', '.active=?'], [$mapId, 1], $options);
    }

    /** @param array<string,mixed> $options */
    public function findChildren(int $layerId, array $options = []): Collection|null
    {
        $sql = <<<'SQL'
      SELECT ml.id 
        FROM tl_cowegis_map_layer ml 
  INNER JOIN tl_cowegis_layer l 
          ON l.id = ml.layerId
       WHERE l.pid = :pid
         AND ml.active = '1'
       ORDER BY l.sorting
SQL;

        $result = $this->connection->executeQuery($sql, ['pid' => $layerId]);

        return $this->findMultipleByIds($result->fetchFirstColumn(), $options);
    }

    /** @param array<string,mixed> $options */
    public function findActiveLayer(int $mapId, int $mapLayerId, array $options = []): MapLayerModel|null
    {
        return $this->findOneBy(['.pid=?', '.layerId=?', 'active=?'], [$mapId, $mapLayerId, '1'], $options);
    }

    /** @param array<string,mixed> $options */
    public function findLayer(int $mapId, int $layerId, array $options = []): MapLayerModel|null
    {
        return $this->findOneBy(['.pid=?', '.layerId=?'], [$mapId, $layerId], $options);
    }
}
