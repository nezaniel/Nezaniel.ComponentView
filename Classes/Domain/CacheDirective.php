<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Domain;

use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\Flow\Annotations as Flow;

/**
 * A cache directive to resolve nested cache hits and misses alike
 */
#[Flow\Proxy(false)]
final class CacheDirective
{
    public function __construct(
        public readonly string $cacheEntryIdentifier,
        public readonly NodeAggregateIdentifier $nodeAggregateIdentifier,
        public readonly ?NodeName $nodeName,
        public readonly ?RenderingEntryPoint $entryPoint
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function serializeForCache(): array
    {
        return [
            'cacheEntryIdentifier' => $this->cacheEntryIdentifier,
            'nodeAggregateIdentifier' => (string)$this->nodeAggregateIdentifier,
            'nodeName' => $this->nodeName ? (string)$this->nodeName : null,
            'entryPoint' => $this->entryPoint?->serializeForCache()
        ];
    }
}
