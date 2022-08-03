<?php

/*
 * This file is part of the Nezaniel.ComponentView package
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Tests\Unit\Fixtures;

use Nezaniel\ComponentView\Domain\ComponentInterface;

final class MySubComponent implements ComponentInterface
{
    public function __construct(
        private string $content
    ){
    }

    public function render(): string
    {
        return $this->content;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'content' => $this->content
        ];
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
