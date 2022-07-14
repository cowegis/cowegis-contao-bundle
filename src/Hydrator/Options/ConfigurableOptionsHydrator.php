<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Hydrator\Options;

use Contao\Model;
use Contao\StringUtil;
use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Core\Definition\HasOptions;
use Cowegis\Core\Definition\Options;
use Cowegis\Core\Definition\Point;
use Cowegis\Core\Provider\Context;

use function array_map;
use function assert;
use function count;
use function is_int;

abstract class ConfigurableOptionsHydrator implements Hydrator
{
    protected const OPTIONS = [];

    protected const CONDITIONAL_OPTIONS = [];

    protected const POINT_OPTIONS = [];

    public function supports(object $data, object $definition): bool
    {
        if (! $this->supportsDefinition($definition)) {
            return false;
        }

        return $this->supportsData($data);
    }

    public function hydrate(object $data, object $definition, Context $context, Hydrator $hydrator): void
    {
        assert($data instanceof Model);

        $options = $this->determineOptions($definition);

        // phpcs:disable SlevomatCodingStandard.Classes.DisallowLateStaticBindingForConstants.DisallowedLateStaticBindingForConstant
        $this->hydrateOptions($data, $options, static::OPTIONS);
        $this->hydrateConditionalOptions($data, $options, static::CONDITIONAL_OPTIONS);
        $this->hydratePointOptions($data, $options, static::POINT_OPTIONS);
    }

    /** @param array<int|string,string> $keys */
    protected function hydrateOptions(Model $model, Options $options, array $keys): void
    {
        foreach ($keys as $target => $source) {
            if (is_int($target)) {
                $target = $source;
            }

            if ($model->{$source} === null) {
                continue;
            }

            $options->set($target, $model->{$source});
        }
    }

    /** @param array<string,array<int|string,string>> $conditions */
    protected function hydrateConditionalOptions(Model $data, Options $options, array $conditions): void
    {
        foreach ($conditions as $trigger => $keys) {
            if (! $data->{$trigger}) {
                continue;
            }

            $this->hydrateOptions($data, $options, $keys);
        }
    }

    /** @param array<int|string,string> $keys */
    private function hydratePointOptions(Model $model, Options $options, array $keys): void
    {
        foreach ($keys as $target => $source) {
            if (is_int($target)) {
                $target = $source;
            }

            if ($model->{$source} === null) {
                continue;
            }

            $values = StringUtil::trimsplit(',', $model->{$source});
            $values = array_map('intval', $values);

            if (count($values) === 1) {
                $values[1] = $values[0];
            }

            $options->set($target, new Point($values[0], $values[1]));
        }
    }

    protected function supportsDefinition(object $definition): bool
    {
        return $definition instanceof HasOptions;
    }

    protected function supportsData(object $data): bool
    {
        return $data instanceof Model;
    }

    protected function determineOptions(object $definition): Options
    {
        assert($definition instanceof HasOptions);

        return $definition->options();
    }
}
