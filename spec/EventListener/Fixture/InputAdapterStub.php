<?php

declare(strict_types=1);

namespace spec\Cowegis\Bundle\Contao\EventListener\Fixture;

use Contao\CoreBundle\Framework\Adapter;
use Contao\Input;

/**
 * Prophecy cannot stub the magic {@see Adapter::__call()}, so the input adapter is faked with a
 * concrete subclass that serves predefined GET parameters.
 *
 * @extends Adapter<Input>
 */
final class InputAdapterStub extends Adapter
{
    /** @param array<string, string|null> $parameters */
    public function __construct(private readonly array $parameters = [])
    {
        parent::__construct(Input::class);
    }

    public function get(string $key): string|null
    {
        return $this->parameters[$key] ?? null;
    }
}
