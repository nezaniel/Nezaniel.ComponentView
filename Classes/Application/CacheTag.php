<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Application;

use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
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
        ?string $workspaceName
    ): self {
        return new self('Everything' . self::renderWorkspacePrefix($workspaceName));
    }

    final public static function forNode(
        NodeInterface $node,
        ?string $workspaceName
    ): self {
        return new self('Node' . self::renderWorkspacePrefix($workspaceName) . '_' . $node->getNodeAggregateIdentifier());
    }

    final public static function forAncestorNode(
        NodeInterface $node,
        ?string $workspaceName
    ): self {
        return new self('Ancestor' . self::renderWorkspacePrefix($workspaceName) . '_' . $node->getNodeAggregateIdentifier());
    }

    final public static function forNodeTypeName(
        NodeTypeName $nodeTypeName,
        ?string $workspaceName
    ): self {
        return new self('NodeType' . self::renderWorkspacePrefix($workspaceName) . '_' . \strtr($nodeTypeName->getValue(), '.:', '_-'));
    }

    final public static function forAsset(
        string $assetIdentifier,
        ?string $workspaceName = null
    ): self {
        return new self('Asset' . self::renderWorkspacePrefix($workspaceName) . '_' . $assetIdentifier);
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

    private static function renderWorkspacePrefix(?string $workspaceName): string
    {
        return $workspaceName ? '_%' . $workspaceName . '%' : '';
    }
}
