<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use Ausi\SlugGenerator\SlugGeneratorInterface;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

final class AliasGenerator
{
    public function __construct(
        private readonly SlugGeneratorInterface $slugGenerator,
        private readonly Connection $connection,
    ) {
    }

    #[AsCallback('tl_cowegis_map', 'fields.alias.save', priority: 128)]
    #[AsCallback('tl_cowegis_marker', 'fields.alias.save', priority: 128)]
    #[AsCallback('tl_cowegis_layer', 'fields.alias.save', priority: 128)]
    #[AsCallback('tl_cowegis_icon', 'fields.alias.save', priority: 128)]
    #[AsCallback('tl_cowegis_popup', 'fields.alias.save', priority: 128)]
    #[AsCallback('tl_cowegis_control', 'fields.alias.save', priority: 128)]
    #[AsCallback('tl_cowegis_style', 'fields.alias.save', priority: 128)]
    public function __invoke(mixed $value, DataContainer $dataContainer): string
    {
        if ($value || ! $dataContainer->activeRecord) {
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

    private function isSlugDuplicate(string $slug, string $table, int $recordId): bool
    {
        $statement = $this->connection->createQueryBuilder()
            ->select('count(id)')
            ->from($table)
            ->where('alias = :alias')
            ->andWhere('id != :id')
            ->setParameter('alias', $slug)
            ->setParameter('id', $recordId)
            ->executeQuery();

        return $statement->fetchOne() > 0;
    }
}
