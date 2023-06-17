<?php

/*
 * This file is part of the Nezaniel.ComponentView package
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Tests\Unit\Fixtures;

use Nezaniel\ComponentView\Domain\AbstractComponent;

final readonly class MySubComponent extends AbstractComponent
{
    public function __construct(
        private string $content
    ){
    }

    public function render(): string
    {
        return $this->content;
    }
}
