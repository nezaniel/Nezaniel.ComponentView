<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Domain;

use Neos\Flow\Annotations as Flow;

/**
 * A collection of self rendering components
 *
 * @implements \IteratorAggregate<ComponentInterface|string>
 */
#[Flow\Proxy(false)]
final readonly class ComponentCollection extends AbstractComponent implements
    ComponentContainerInterface,
    \IteratorAggregate
{
    /**
     * @var array<ComponentInterface|string>
     */
    private array $components;

    public function __construct(ComponentInterface|string ...$components)
    {
        $this->components = $components;
    }

    public function union(self $other): self
    {
        return new self(...array_merge($this->components, $other->components));
    }

    public function isEmpty(): bool
    {
        return empty($this->components) || $this->components === ['<span></span>'];
    }

    public function render(): string
    {
        return implode('', $this->components);
    }

    /**
     * @return \Traversable<ComponentInterface|string>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->components;
    }
}
