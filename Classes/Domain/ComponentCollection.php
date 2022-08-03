<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Domain;

use Neos\Flow\Annotations as Flow;

/**
 * A collection of self rendering components
 */
#[Flow\Proxy(false)]
final class ComponentCollection implements ComponentInterface, ComponentContainerInterface
{
    /**
     * @var array<int,ComponentInterface|string>
     */
    private array $components;

    public function __construct(ComponentInterface|string ...$components)
    {
        $this->components = $components;
    }

    public function isEmpty(): bool
    {
        return empty($this->components);
    }

    public function render(): string
    {
        return implode('', $this->components);
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
