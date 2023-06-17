<?php

/*
 * This file is part of the Nezaniel.ComponentView package
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Tests\Unit\Fixtures;

use Nezaniel\ComponentView\Domain\AbstractComponent;

final readonly class MySubComponents extends AbstractComponent
{
    /**
     * @var array<int|string,MySubComponent>
     */
    private array $subComponents;

    public function __construct(MySubComponent ...$subComponents)
    {
        $this->subComponents = $subComponents;
    }

    public function render(): string
    {
        return implode('', $this->subComponents);
    }
}
