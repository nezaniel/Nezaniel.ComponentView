<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Application;

use Neos\Flow\Annotations as Flow;

/**
 * The cache tag value object collection
 */
#[Flow\Proxy(false)]
final class CacheTags
{
    /**
     * @var array<int,CacheTag>
     */
    private array $tags;

    public function __construct(CacheTag ...$tags)
    {
        $this->tags = $tags;
    }

    /**
     * @return array<int,string>
     */
    public function toStringArray(): array
    {
        return array_map(
            fn (CacheTag $tag): string => $tag->value,
            $this->tags
        );
    }

    public function union(self $other): self
    {
        return new self(...array_merge($this->tags, $other->tags));
    }
}
