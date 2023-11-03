<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Domain;

use Neos\Flow\Annotations as Flow;

/**
 * A cache segment defined by its cache directive and optional rendered content
 */
#[Flow\Proxy(false)]
final readonly class CacheSegment implements ComponentInterface, ComponentContainerInterface
{
    public function __construct(
        public CacheDirective $cacheDirective,
        public ComponentInterface|string|null $content
    ) {
    }

    public function render(): string
    {
        return is_string($this->content) ? $this->content : ($this->content?->render() ?: '');
    }

    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * @return array<string,mixed>
     */
    public function serializeForCache(): array
    {
        return [
            '__class' => self::class,
            'cacheDirective' => $this->cacheDirective->serializeForCache()
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'cacheDirective' => $this->cacheDirective
        ];
    }

    public function isEmpty(): bool
    {
        return $this->content instanceof ComponentContainerInterface
            ? $this->content->isEmpty()
            : false;
    }
}
