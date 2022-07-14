<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Icon;

use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Hydrator\Options\ConfigurableOptionsHydrator;
use Cowegis\Bundle\Contao\Model\IconModel;
use Cowegis\Bundle\Contao\Model\MarkerModel;
use Cowegis\Core\Definition\Definition;
use Cowegis\Core\Definition\Icon\Icon;
use Cowegis\Core\Provider\Context;

use function assert;
use function is_a;

abstract class IconTypeHydrator extends ConfigurableOptionsHydrator
{
    public function supports(object $data, object $definition): bool
    {
        if (! $definition instanceof Icon) {
            return false;
        }

        if ($data instanceof IconModel) {
            return $data->type === $this->supportedType();
        }

        if ($data instanceof MarkerModel) {
            return is_a($definition, $this->supportedDefinition());
        }

        return false;
    }

    public function hydrate(object $data, object $definition, Context $context, Hydrator $hydrator): void
    {
        assert($definition instanceof Icon);

        if ($data instanceof IconModel) {
            parent::hydrate($data, $definition, $context, $hydrator);
            $this->hydrateIcon($data, $definition);
        }

        if (! ($data instanceof MarkerModel)) {
            return;
        }

        $this->customizeForMarker($data, $definition);
    }

    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    protected function hydrateIcon(IconModel $iconModel, Icon $icon): void
    {
    }

    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    protected function customizeForMarker(MarkerModel $markerModel, Icon $icon): void
    {
    }

    abstract protected function supportedType(): string;

    /** @return class-string<Definition> */
    abstract protected function supportedDefinition(): string;
}
