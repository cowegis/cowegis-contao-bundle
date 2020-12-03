<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model;

use Contao\Model as BaseModel;

abstract class Model extends BaseModel
{
    /** @SuppressWarnings(PHPMD.ShortMethodName)  */
    public function id(): int
    {
        return (int) $this->id;
    }
}
