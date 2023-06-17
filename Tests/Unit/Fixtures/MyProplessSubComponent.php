<?php

/*
 * This file is part of the Nezaniel.ComponentView package
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Tests\Unit\Fixtures;

use Nezaniel\ComponentView\Domain\AbstractComponent;

final readonly class MyProplessSubComponent extends AbstractComponent
{
    public function render(): string
    {
        return 'weee';
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [];
    }
}
