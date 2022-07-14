<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model;

/**
 * @property numeric-string|int|bool $autoPan
 * @property string                  $autoPanPadding
 * @property string                  $offset
 */
final class PopupModel extends Model
{
    /** @var string */
    protected static $strTable = 'tl_cowegis_popup';
}
