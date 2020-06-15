<?php

declare(strict_types=1);

namespace spec\Cowegis\Bundle\ContaoMaps;

use Cowegis\Bundle\Contao\CowegisContaoBundle;
use PhpSpec\ObjectBehavior;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class CowegisContaoMapsBundleSpec extends ObjectBehavior
{
    public function it_is_initializable() : void
    {
        $this->shouldHaveType(CowegisContaoBundle::class);
    }

    public function it_is_a_kernel_bundle() : void
    {
        $this->shouldBeAnInstanceOf(Bundle::class);
    }
}
