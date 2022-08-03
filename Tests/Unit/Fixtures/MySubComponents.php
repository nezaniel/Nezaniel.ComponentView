<?php

/*
 * This file is part of the Nezaniel.ComponentView package
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Tests\Unit\Fixtures;

use Nezaniel\ComponentView\Domain\ComponentInterface;

final class MySubComponents implements ComponentInterface
{
    /**
     * @var array<int,MySubComponent>
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

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->subComponents;
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
