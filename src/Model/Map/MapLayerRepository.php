<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model\Map;

use Contao\Model\Collection;
use Doctrine\DBAL\Connection;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;
use PDO;

final class MapLayerRepository extends ContaoRepository
{
    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct(MapLayerModel::class);

        $this->connection = $connection;
    }

    public function findActive(int $mapId, array $options = []) : ?Collection
    {
        $options['sorting'] = $options['sorting'] ?? '.sorting';

        return $this->findBy(['.pid=?', '.active=?'], [$mapId, 1], $options);
    }

    public function findChildren(int $layerId, array $options = []) : ?Collection
    {
        $options['sorting'] = $options['sorting'] ?? '.sorting';

        $sql = <<<'SQL'
      SELECT ml.id 
        FROM tl_cowegis_map_layer ml 
  INNER JOIN tl_cowegis_layer l 
          ON l.id = ml.layerId
       WHERE l.pid = :pid
         AND ml.active = '1'
       ORDER BY l.sorting
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue('pid', $layerId);
        $statement->execute();

        return $this->findMultipleByIds($statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function findActiveLayer(int $mapId, int $mapLayerId, array $options = []) : ?MapLayerModel
    {
        return $this->findOneBy(['.pid=?', '.layerId=?', 'active=?'], [$mapId, $mapLayerId, '1'], $options);
    }

    public function findLayer(int $mapId, int $layerId, array $options = []): ?MapLayerModel
    {
        return $this->findOneBy(['.pid=?', '.layerId=?'], [$mapId, $layerId], $options);
    }
}
