<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Application;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Flow\Annotations as Flow;

/**
 * The cache tag value object set
 */
#[Flow\Proxy(false)]
final class CacheTagSet
{
    /**
     * Unique cache tags, indexed by their value
     * @var array<string,CacheTag>
     */
    private array $tags;

    public function __construct(CacheTag ...$tags)
    {
        $uniqueTags = [];
        foreach ($tags as $tag) {
            $uniqueTags[$tag->value] = $tag;
        }

        $this->tags = $uniqueTags;
    }

    public static function forNodeTypeNames(
        ContentRepositoryId $contentRepositoryId,
        ContentStreamId $contentStreamId,
        NodeTypeNames $nodeTypeNames
    ): self {
        return new self(...array_map(
            fn (NodeTypeName $nodeTypeName): CacheTag => CacheTag::forNodeTypeName(
                $contentRepositoryId,
                $contentStreamId,
                $nodeTypeName
            ),
            iterator_to_array($nodeTypeNames)
        ));
    }

    public function add(CacheTag $cacheTag): self
    {
        $tags = $this->tags;
        $tags[] = $cacheTag;

        return new self(...$tags);
    }

    /**
     * @return array<int,string>
     */
    public function toStringArray(): array
    {
        return array_map(
            fn (CacheTag $tag): string => $tag->value,
            array_values($this->tags)
        );
    }

    public function union(self $other): self
    {
        return new self(...array_merge($this->tags, $other->tags));
    }
}
