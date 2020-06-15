<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;

final class LeafletMigration extends AbstractMigration
{
    public function shouldRun() : bool
    {
        // TODO: Implement shouldRun() method.
    }

    public function run() : MigrationResult
    {
        // TODO:
        // Migrate tl_leaflet_map to tl_cowegis_map
        //  - Rename closeOnClick to closePopupOnClick

        // Migrate tl_leaflet_layer to tl_cowegis_layer
    }
}
