<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Filter;

use Cowegis\Bundle\Contao\Event\ApplyFilterRuleEvent;
use Cowegis\Bundle\Contao\Model\MarkerModel;
use Cowegis\Core\Filter\Rule\BboxRule;
use Cowegis\Core\Filter\Rule\DistanceRule;
use Cowegis\Core\Filter\Rule\KeywordRule;
use function is_a;

final class ApplyFilterRuleMarkerListener
{
    public function __invoke(ApplyFilterRuleEvent $event) : void
    {
        if (!is_a($event->modelClass(), MarkerModel::class, true)) {
            return;
        }

        $rule = $event->rule();

        switch (true) {
            case $rule instanceof KeywordRule:
                $this->applyKeywordRule($rule, $event);
                break;

            case $rule instanceof DistanceRule:
                $this->applyDistanceRule($rule, $event);
                break;

            case $rule instanceof BboxRule:
                $this->applyBboxRule($rule, $event);
                break;
        }
    }

    private function applyKeywordRule(KeywordRule $rule, ApplyFilterRuleEvent $event) : void
    {
        $event->withColumns('.title LIKE ?');
        $event->withValues('%' . $rule->keyword() . '%');
    }

    private function applyDistanceRule(DistanceRule $rule, ApplyFilterRuleEvent $event) : void
    {
        $center   = $rule->coordinates();
        $query = <<<'SQL'
(round(
  sqrt(
    power( 2 * pi() / 360 * (? - .latitude) * 6371,2)
    + power( 2 * pi() / 360 * (? - .longitude) * 6371 * COS( 2 * pi() / 360 * (? + .latitude) * 0.5 ),2)
  )
) <= ?)
SQL;

        $radius = $rule->radius();
        $radius = $radius === 0 ? 0 : $radius / 1000;

        $event->withColumns($query);
        $event->withValues($center->latitude(), $center->longitude(), $center->latitude(), $radius);
    }

    private function applyBboxRule(BboxRule $rule, ApplyFilterRuleEvent $event) : void
    {
        $boundingBox = $rule->boundingBox();
        $southWest   = $boundingBox->southWest();
        $northEast   = $boundingBox->northEast();

        $event->withColumns('.latitude >= ? AND .latitude <= ?', '.longitude >= ? AND .longitude <= ?');
        $event->withValues(
            $southWest->latitude(),
            $northEast->latitude(),
            $southWest->longitude(),
            $northEast->longitude()
        );
    }
}
