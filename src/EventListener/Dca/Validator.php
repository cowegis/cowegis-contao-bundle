<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use Contao\DataContainer;
use Contao\StringUtil;
use Cowegis\Core\Definition\LatLng;
use InvalidArgumentException;
use Netzmacht\Contao\Toolkit\Dca\Manager;
use Symfony\Contracts\Translation\TranslatorInterface as Translator;
use function explode;
use function is_array;
use function preg_match;

final class Validator
{
    /**
     * Translator.
     *
     * @var Translator;
     */
    private $translator;

    /**
     * Data container manager.
     *
     * @var Manager
     */
    private $dcaManager;

    /**
     * Validator constructor.
     *
     * @param Manager    $dcaManager Data container manager.
     * @param Translator $translator Translator.
     */
    public function __construct(Manager $dcaManager, Translator $translator)
    {
        $this->translator = $translator;
        $this->dcaManager = $dcaManager;
    }

    /**
     * Validate coordinates.
     *
     * @param mixed         $value         Given value.
     * @param DataContainer $dataContainer Data container driver.
     *
     * @return mixed
     *
     * @throws InvalidArgumentException When invalid coordinates given.
     */
    public function validateCoordinates($value, $dataContainer)
    {
        if (!$value && !$this->isRequired($dataContainer)) {
            return $value;
        }

        try {
            LatLng::fromString($value);
        } catch (\Exception $e) {
            throw new InvalidArgumentException(
                $this->translator->trans('cowegis.invalidCoordinates', [$value], 'contao_leaflet'),
                0,
                $e
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
     * @return mixed
     *
     * @throws InvalidArgumentException When invalid coordinates given.
     */
    public function validateMultipleCoordinates($values, $dataContainer)
    {
        if (!is_array($values)) {
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
     * @return mixed
     *
     * @throws InvalidArgumentException When invalid coordinates given.
     */
    public function validateMultipleCoordinateSets($values, $dataContainer)
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
     * @return string
     * @throws InvalidArgumentException When invalid value given.
     */
    public function validateAlias($value)
    {
        if (preg_match('/^[A-Za-z_]+[A-Za-z0-9_]+$/', $value) !== 1) {
            throw new InvalidArgumentException(
                $this->translator->trans('cowegis.invalidAlias', [], 'contao_cowegis')
            );
        }

        return $value;
    }

    /**
     * Check if value is required.
     *
     * @param DataContainer $dataContainer Data container driver.
     *
     * @return bool
     */
    private function isRequired($dataContainer): bool
    {
        $definition = $this->dcaManager->getDefinition($dataContainer->table);

        if ($definition->get(['fields', $dataContainer->field, 'eval', 'mandatory'])) {
            return true;
        }

        return false;
    }
}
