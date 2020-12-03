<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use Ausi\SlugGenerator\SlugGeneratorInterface;
use Contao\DataContainer;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

final class AliasGenerator
{
    /** @var SlugGeneratorInterface */
    private $slugGenerator;

    /** @var Connection */
    private $connection;

    public function __construct(SlugGeneratorInterface $slugGenerator, Connection $connection)
    {
        $this->slugGenerator = $slugGenerator;
        $this->connection    = $connection;
    }

    /** @param mixed $value */
    public function __invoke($value, DataContainer $dataContainer): string
    {
        if ($value) {
            return $value;
        }

        $value = StringUtil::prepareSlug($dataContainer->activeRecord->title);
        $slug  = $this->slugGenerator->generate($value);

        $unique = $slug;
        $suffix = 2;
        while ($this->isSlugDuplicate($unique, $dataContainer->table, (int) $dataContainer->id)) {
            $unique = $slug . '_' . $suffix;
            $suffix++;
        }

        return $unique;
    }

    private function isSlugDuplicate(string $slug, string $table, int $id): bool
    {
        $statement = $this->connection->createQueryBuilder()
            ->select('count(id)')
            ->from($table)
            ->where('alias = :alias')
            ->andWhere('id != :id')
            ->setParameter('alias', $slug)
            ->setParameter('id', $id)
            ->execute();

        return $statement->fetchColumn() > 0;
    }
}
