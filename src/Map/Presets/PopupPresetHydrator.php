<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Presets;

use Contao\StringUtil;
use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Map\Options\ConfigurableOptionsHydrator;
use Cowegis\Bundle\Contao\Model\PopupModel;
use Cowegis\Core\Definition\Point;
use Cowegis\Core\Definition\Preset\PopupPreset;
use Cowegis\Core\Provider\Context;

use function array_map;
use function assert;

final class PopupPresetHydrator extends ConfigurableOptionsHydrator
{
    /** @var list<string>|array<string,string> */
    protected static array $options = [
        'maxWidth',
        'minWidth',
        'maxHeight',
        'className',
        'autoPan',
        'keepInView',
        'autoClose',
        'closeButton',
        'closeOnClick',
        'closeOnEscapeKey',
    ];

    public function hydrate(object $data, object $definition, Context $context, Hydrator $hydrator): void
    {
        assert($data instanceof PopupModel);
        assert($definition instanceof PopupPreset);

        parent::hydrate($data, $definition, $context, $hydrator);

        if ($data->offset) {
            $definition->options()->set(
                'offset',
                Point::fromArray(array_map('intval', StringUtil::trimsplit(',', $data->offset))),
            );
        }

        $this->hydrateAutoPan($data, $definition);
    }

    protected function supportsDefinition(object $definition): bool
    {
        return $definition instanceof PopupPreset;
    }

    protected function supportsData(object $data): bool
    {
        return $data instanceof PopupModel;
    }

    private function hydrateAutoPan(PopupModel $model, PopupPreset $definition): void
    {
        if (! (bool) $model->autoPan) {
            return;
        }

        $padding = array_map(
            /** return list<int> */
            static function (string $value): array {
                return array_map('intval', StringUtil::trimsplit(',', $value));
            },
            StringUtil::deserialize($model->autoPanPadding, true),
        );

        if ($padding[0] === $padding[1]) {
            if (! empty($padding[0])) {
                $definition->options()->set('autoPanPadding', Point::fromArray($padding[0]));
            }

            return;
        }

        if ($padding[0]) {
            $definition->options()->set('autoPanPaddingTopLeft', Point::fromArray($padding[0]));
        }

        if (! $padding[1]) {
            return;
        }

        $definition->options()->set('autoPanPaddingBottomRight', Point::fromArray($padding[1]));
    }
}
