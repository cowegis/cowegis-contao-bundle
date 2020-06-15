<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Hydrator\Options;

use Contao\Model;
use Cowegis\Core\Provider\Context;
use Cowegis\Bundle\Contao\Model\Map\MapModel;
use Cowegis\Core\Constraint\FloatConstraint;
use Cowegis\Core\Constraint\LatLngConstraint;
use Cowegis\Core\Definition\Point;
use Cowegis\Core\Definition\Map\Map;
use Cowegis\Core\Definition\Map\View;
use Cowegis\Core\Definition\Options;
use function array_map;
use function assert;
use function explode;

final class ViewHydrator extends ConfigurableOptionsHydrator
{
    protected const OPTIONS = [
        'maxZoom',
    ];

    public function hydrate(object $data, object $definition, Context $context) : void
    {
        assert($data instanceof MapModel);
        assert($definition instanceof View);

        parent::hydrate($data, $definition, $context);

        $options = $definition->options();

        $definition->changeView(
            (new LatLngConstraint())->filter($data->center),
            (new FloatConstraint())->filter($data->zoom)
        );

        $this->hydrateBoundsPadding($data, $options);
    }

    protected function supportsData(object $data) : bool
    {
        return $data instanceof MapModel;
    }

    protected function supportsDefinition(object $definition) : bool
    {
        return $definition instanceof View;
    }

    private function hydrateBoundsPadding(Model $model, Options $options) : void
    {
        if (! $model->boundsPadding) {
            return;
        }

        $value = array_map('intval', explode(',', $model->boundsPadding, 4));

        if (count($value) === 4) {
            $options->set('boundsPaddingTopLeft', new Point((int) $value[0], (int) $value[1]));
            $options->set('boundsPaddingBottomRight', new Point((int) $value[2], (int) $value[3]));
        } elseif (count($value) === 2) {
            $options->set('boundsPadding', new Point((int) $value[0], (int) $value[1]));
        }
    }
}
