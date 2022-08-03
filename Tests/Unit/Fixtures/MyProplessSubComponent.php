<?php

/*
 * This file is part of the Nezaniel.ComponentView package
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Tests\Unit\Fixtures;

use Nezaniel\ComponentView\Domain\ComponentInterface;

final class MyProplessSubComponent implements ComponentInterface
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

    public function __toString(): string
    {
        return $this->render();
    }
}
