<?php

/*
 * This file is part of the Nezaniel.ComponentView package
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Tests\Unit\Fixtures;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\AbsoluteNodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

final class TestingSubgraph implements ContentSubgraphInterface
{
    public function findNodeById(NodeAggregateId $nodeAggregateId): ?Node
    {
        return null;
    }

    public function findChildNodes(NodeAggregateId $parentNodeAggregateId, Filter\FindChildNodesFilter $filter): Nodes
    {
        return Nodes::createEmpty();
    }

    public function countChildNodes(NodeAggregateId $parentNodeAggregateId, Filter\CountChildNodesFilter $filter): int
    {
        return 0;
    }

    public function findParentNode(NodeAggregateId $childNodeAggregateId): ?Node
    {
        return null;
    }

    public function findSucceedingSiblingNodes(
        NodeAggregateId $siblingNodeAggregateId,
        Filter\FindSucceedingSiblingNodesFilter $filter
    ): Nodes {
        return Nodes::createEmpty();
    }

    public function findPrecedingSiblingNodes(
        NodeAggregateId $siblingNodeAggregateId,
        Filter\FindPrecedingSiblingNodesFilter $filter
    ): Nodes {
        return Nodes::createEmpty();
    }

    public function findDescendantNodes(
        NodeAggregateId $entryNodeAggregateId,
        Filter\FindDescendantNodesFilter $filter
    ): Nodes {
        return Nodes::createEmpty();
    }

    public function countDescendantNodes(
        NodeAggregateId $entryNodeAggregateId,
        Filter\CountDescendantNodesFilter $filter
    ): int {
        return 0;
    }

    public function findSubtree(NodeAggregateId $entryNodeAggregateId, Filter\FindSubtreeFilter $filter): ?Subtree
    {
        return null;
    }

    public function findReferences(NodeAggregateId $nodeAggregateId, Filter\FindReferencesFilter $filter): References
    {
        return References::fromArray([]);
    }

    public function countReferences(NodeAggregateId $nodeAggregateId, Filter\CountReferencesFilter $filter): int
    {
        return 0;
    }

    public function findBackReferences(
        NodeAggregateId $nodeAggregateId,
        Filter\FindBackReferencesFilter $filter
    ): References {
        return References::fromArray([]);
    }

    public function countBackReferences(NodeAggregateId $nodeAggregateId, Filter\CountBackReferencesFilter $filter): int
    {
        return 0;
    }

    public function findNodeByPath(NodePath|NodeName $path, NodeAggregateId $startingNodeAggregateId): ?Node
    {
        return null;
    }

    public function retrieveNodePath(NodeAggregateId $nodeAggregateId): AbsoluteNodePath
    {
        throw new \InvalidArgumentException(
            'Failed to retrieve node path for node "' . $nodeAggregateId->value . '"',
            1687513836
        );
    }

    public function countNodes(): int
    {
        return 0;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [];
    }

    public function findRootNodeByType(NodeTypeName $nodeTypeName): ?Node
    {
        return null;
    }

    public function findAncestorNodes(
        NodeAggregateId $entryNodeAggregateId,
        Filter\FindAncestorNodesFilter $filter
    ): Nodes {
        return Nodes::createEmpty();
    }

    public function countAncestorNodes(
        NodeAggregateId $entryNodeAggregateId,
        Filter\CountAncestorNodesFilter $filter
    ): int {
        return 0;
    }

    public function findClosestNode(NodeAggregateId $entryNodeAggregateId, Filter\FindClosestNodeFilter $filter): ?Node
    {
        return null;
    }

    public function findNodeByAbsolutePath(AbsoluteNodePath $path): ?Node
    {
        return null;
    }

    public function getContentRepositoryId(): ContentRepositoryId
    {
        return ContentRepositoryId::fromString('default');
    }

    public function getWorkspaceName(): WorkspaceName
    {
        return WorkspaceName::forLive();
    }

    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return DimensionSpacePoint::createWithoutDimensions();
    }

    public function getVisibilityConstraints(): VisibilityConstraints
    {
        return VisibilityConstraints::withoutRestrictions();
    }
}
