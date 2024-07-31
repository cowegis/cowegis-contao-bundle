<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use Contao\DataContainer;
use Contao\StringUtil;
use Cowegis\Core\Definition\LatLng;
use InvalidArgumentException;
use Netzmacht\Contao\Toolkit\Dca\Manager;
use Symfony\Contracts\Translation\TranslatorInterface as Translator;
use Throwable;

use function explode;
use function is_array;
use function preg_match;

final class Validator
{
    /**
     * @param Manager    $dcaManager Data container manager.
     * @param Translator $translator Translator.
     */
    public function __construct(private readonly Manager $dcaManager, private readonly Translator $translator)
    {
    }

    /**
     * Validate coordinates.
     *
     * @param mixed         $value         Given value.
     * @param DataContainer $dataContainer Data container driver.
     *
     * @throws InvalidArgumentException When invalid coordinates given.
     */
    public function validateCoordinates(mixed $value, DataContainer $dataContainer): mixed
    {
        if (! $value && ! $this->isRequired($dataContainer)) {
            return $value;
        }

        try {
            LatLng::fromString($value);
        } catch (Throwable $e) {
            throw new InvalidArgumentException(
                $this->translator->trans('cowegis.invalidCoordinates', [$value], 'contao_cowegis'),
                0,
                $e,
            );
        }

        return $value;
    }

    /**
     * Validate multiple coordinates.
     *
     * @param mixed         $values        Given value.
     * @param DataContainer $dataContainer Data container driver.
     *
     * @throws InvalidArgumentException When invalid coordinates given.
     */
    public function validateMultipleCoordinates(mixed $values, DataContainer $dataContainer): mixed
    {
        if (! is_array($values)) {
            $lines = explode("\n", $values);
        } else {
            $lines = $values;
        }

        foreach ($lines as $coordinate) {
            $this->validateCoordinates($coordinate, $dataContainer);
        }

        return $values;
    }

    /**
     * Validate multiple coordinate sets.
     *
     * @param mixed         $values        Given value.
     * @param DataContainer $dataContainer Data container driver.
     *
     * @throws InvalidArgumentException When invalid coordinates given.
     */
    public function validateMultipleCoordinateSets(mixed $values, DataContainer $dataContainer): mixed
    {
        $sets = StringUtil::deserialize($values, true);
        foreach ($sets as $lines) {
            $this->validateMultipleCoordinates($lines, $dataContainer);
        }

        return $values;
    }

    /**
     * Validate an alias.
     *
     * @param string $value Given value.
     *
     * @throws InvalidArgumentException When invalid value given.
     */
    public function validateAlias(string $value): string
    {
        if (preg_match('/^[A-Za-z_]+[A-Za-z0-9_]+$/', $value) !== 1) {
            throw new InvalidArgumentException(
                $this->translator->trans('cowegis.invalidAlias', [], 'contao_cowegis'),
            );
        }

        return $value;
    }

    /**
     * Check if value is required.
     *
     * @param DataContainer $dataContainer Data container driver.
     */
    private function isRequired(DataContainer $dataContainer): bool
    {
        $definition = $this->dcaManager->getDefinition($dataContainer->table);

        return (bool) $definition->get(['fields', $dataContainer->field, 'eval', 'mandatory']);
    }
}
