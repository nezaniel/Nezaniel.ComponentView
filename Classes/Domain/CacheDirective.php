<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Domain;

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\Flow\Annotations as Flow;

/**
 * A cache directive to resolve nested cache hits and misses alike
 */
#[Flow\Proxy(false)]
final readonly class CacheDirective
{
    public function __construct(
        public string $cacheEntryId,
        public NodeAggregateId $nodeAggregateId,
        public ?NodeName $nodeName,
        public ?RenderingEntryPoint $entryPoint
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function serializeForCache(): array
    {
        return [
            'cacheEntryId' => $this->cacheEntryId,
            'nodeAggregateId' => $this->nodeAggregateId->value,
            'nodeName' => $this->nodeName?->value,
            'entryPoint' => $this->entryPoint?->serializeForCache()
        ];
    }
}
