<?php

declare(strict_types=1);

namespace spec\Cowegis\Bundle\Contao\Hydrator\Fixture;

use Contao\Model;

/**
 * Contao\Model::__construct() and __set() both reach into System::getContainer(), which is not
 * booted in this spec. Populate $arrData directly to bypass that and get a stable table/id pair.
 */
final class ModelStub extends Model
{
    /** @var string */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected static $strTable = 'tl_cowegis_layer';

    public function __construct(int $recordId)
    {
        $this->arrData = ['id' => $recordId];
    }
}
