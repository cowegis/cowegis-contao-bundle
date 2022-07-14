<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model;

use Cowegis\Core\Definition\DefinitionId\IntegerDefinitionId;
use Cowegis\Core\Definition\Icon\IconId;

/**
 * @property string      $type
 * @property string|null $iconImage
 * @property string|null $iconRetinaImage
 * @property string|null $shadowImage
 * @property string|null $shadowRetinaImage
 */
final class IconModel extends Model
{
    /** @var string */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected static $strTable = 'tl_cowegis_icon';

    public function iconId(): IconId
    {
        return IconId::fromValue(IntegerDefinitionId::fromValue($this->id()));
    }
}
