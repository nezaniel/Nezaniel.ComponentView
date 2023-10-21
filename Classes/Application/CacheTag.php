<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Application;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Flow\Annotations as Flow;

/**
 * The cache tag value object
 */
#[Flow\Proxy(false)]
class CacheTag
{
    protected const PATTERN = '/^[a-zA-Z0-9_%\-&]{1,250}$/';

    private function __construct(
        public readonly string $value
    ) {
    }

    final public static function forEverything(
        ?ContentRepositoryId $contentRepositoryId,
        ?ContentStreamId $contentStreamId,
    ): self {
        return new self(
            'Everything'
            . self::renderContentRepositoryPrefix($contentRepositoryId)
            . self::renderContentStreamPrefix($contentStreamId)
        );
    }

    final public static function forNodeAggregate(
        ContentRepositoryId $contentRepositoryId,
        ?ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
    ): self {
        return new self(
            'NodeAggregate'
            . self::renderContentRepositoryPrefix($contentRepositoryId)
            . self::renderContentStreamPrefix($contentStreamId)
            . '_' . $nodeAggregateId->value
        );
    }

    final public static function forNodeAggregateFromNode(Node $node): self
    {
        return self::forNodeAggregate(
            $node->subgraphIdentity->contentRepositoryId,
            $node->subgraphIdentity->contentStreamId,
            $node->nodeAggregateId
        );
    }

    final public static function forAncestorNode(
        ContentRepositoryId $contentRepositoryId,
        ?ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
    ): self {
        return new self(
            'Ancestor'
            . self::renderContentRepositoryPrefix($contentRepositoryId)
            . self::renderContentStreamPrefix($contentStreamId)
            . '_' . $nodeAggregateId->value
        );
    }

    final public static function forAncestorNodeFromNode(Node $node): self
    {
        return self::forAncestorNode(
            $node->subgraphIdentity->contentRepositoryId,
            $node->subgraphIdentity->contentStreamId,
            $node->nodeAggregateId
        );
    }

    final public static function forNodeTypeName(
        ContentRepositoryId $contentRepositoryId,
        ?ContentStreamId $contentStreamId,
        NodeTypeName $nodeTypeName,
    ): self {
        return new self(
            'NodeType'
            . self::renderContentRepositoryPrefix($contentRepositoryId)
            . self::renderContentStreamPrefix($contentStreamId)
            . '_' . \strtr($nodeTypeName->value, '.:', '_-')
        );
    }

    final public static function forAsset(
        string $assetIdentifier,
        ?ContentStreamId $contentStreamId = null,
    ): self {
        return new self(
            'Asset'
            . self::renderContentStreamPrefix($contentStreamId)
            . '_' . $assetIdentifier
        );
    }

    final public static function fromString(string $string): self
    {
        if (preg_match(self::PATTERN, $string) !== 1) {
            throw new \InvalidArgumentException(
                'Given value "' . $string . '" is no valid cache tag, must match the defined pattern.',
                1658093413
            );
        }

        return new self($string);
    }

    private static function renderContentStreamPrefix(?ContentStreamId $contentStreamId): string
    {
        return $contentStreamId ? '_%' . $contentStreamId->value . '%' : '';
    }

    private static function renderContentRepositoryPrefix(?ContentRepositoryId $contentRepositoryId): string
    {
        return $contentRepositoryId ? '_%' . $contentRepositoryId->value . '%' : '';
    }
}
