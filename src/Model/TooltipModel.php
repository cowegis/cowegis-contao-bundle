<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model;

/**
 * @property string $offset
 */
final class TooltipModel extends Model
{
    /** @var string */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected static $strTable = 'tl_cowegis_tooltip';
}
