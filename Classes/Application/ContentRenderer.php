<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Application;

use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\Flow\Annotations as Flow;
use Nezaniel\ComponentView\Domain\ComponentInterface;
use Nezaniel\ComponentView\Domain\CacheDirective;
use Nezaniel\ComponentView\Domain\CacheSegment;
use Nezaniel\ComponentView\Domain\ComponentCollection;

/**
 * The content renderer application service
 */
#[Flow\Scope("singleton")]
final class ContentRenderer extends AbstractComponentFactory
{
    public function __construct(
        private readonly NodeMetadataWrapperFactory $nodeMetadataWrapperFactory
    ) {
    }

    public function forContentCollectionChildNode(
        Node $documentNode,
        Node $site,
        NodeName $collectionName,
        ContentSubgraphInterface $subgraph,
        bool $inBackend
    ): CacheSegment {
        return $this->resolveCacheSegmentForContentCollection(
            $documentNode,
            $documentNode,
            $site,
            $collectionName,
            $subgraph,
            $inBackend
        );
    }

    public function forContentCollection(
        Node $contentCollection,
        Node $documentNode,
        Node $site,
        ContentSubgraphInterface $subgraph,
        bool $inBackend
    ): CacheSegment {
        return $this->resolveCacheSegmentForContentCollection(
            $contentCollection,
            $documentNode,
            $site,
            null,
            $subgraph,
            $inBackend
        );
    }

    private function resolveCacheSegmentForContentCollection(
        Node $node,
        Node $documentNode,
        Node $site,
        ?NodeName $collectionName,
        ContentSubgraphInterface $subgraph,
        bool $inBackend
    ): CacheSegment {
        $workspaceName = $this->getWorkspaceName($node);
        $cacheEntryIdentifier = 'node_' . $node->nodeAggregateId->value
            . '_' . $workspaceName->value . '_' . $inBackend . ($collectionName ? '_' . $collectionName->value : '');

        $component = $this->componentCache->findComponent(
            $cacheEntryIdentifier,
            $subgraph,
            $documentNode,
            $site,
            $inBackend
        );

        if (is_null($component)) {
            $contentCollection = $collectionName
                ? $subgraph->findChildNodeConnectedThroughEdgeName(
                    $node->nodeAggregateId,
                    $collectionName
                ) : $node;
            $cacheTags = new CacheTags(
                CacheTag::forAncestorNode($contentCollection, $workspaceName),
                CacheTag::forNode($contentCollection, $workspaceName)
            );
            $content = new ComponentCollection(... array_map(
                fn (Node $childNode): ComponentInterface => $this->delegate(
                    $childNode,
                    $documentNode,
                    $site,
                    $subgraph,
                    $inBackend,
                    $cacheTags
                ),
                $subgraph->findChildNodes($contentCollection->nodeAggregateId, FindChildNodesFilter::create())->getIterator()->getArrayCopy()
            ));
            if ($content->isEmpty()) {
                $content = new ComponentCollection('<span></span>');
            }

            $component = $inBackend
                ? $this->nodeMetadataWrapperFactory->forNode($contentCollection, $content)
                : $content;

            $this->componentCache->set(
                $cacheEntryIdentifier,
                $component,
                87600,
                $cacheTags
            );
        }

        return new CacheSegment(
            new CacheDirective(
                $cacheEntryIdentifier,
                $node->nodeAggregateId,
                $collectionName,
                null
            ),
            $component
        );
    }

    public function delegate(
        Node $contentNode,
        Node $documentNode,
        Node $site,
        ContentSubgraphInterface $subgraph,
        bool $inBackend,
        CacheTags &$cacheTags
    ): ComponentInterface {
        $contentComponentFactory = $this->resolveContentComponentFactory($contentNode);
        $component = $contentComponentFactory->forContentNode(
            $contentNode,
            $documentNode,
            $site,
            $subgraph,
            $inBackend,
            $cacheTags
        );

        return $inBackend
            ? $this->nodeMetadataWrapperFactory->forNode($contentNode, $component)
            : $component;
    }

    private function resolveContentComponentFactory(Node $contentNode): ContentComponentFactoryInterface
    {
        list($packageKey, $contentName) = explode(':', $contentNode->nodeTypeName->value);

        $contentComponentFactoryClassName = \str_replace('.', '\\', $packageKey) . '\\Integration\\ContentComponentFactory';
        if (!class_exists($contentComponentFactoryClassName)) {
            throw new \InvalidArgumentException(
                'Missing content component factory in package ' . $packageKey
                    . ' for node type ' . $contentNode->nodeTypeName->value,
                1656762670
            );
        }
        $contentComponentFactory = new $contentComponentFactoryClassName();
        if (!$contentComponentFactory instanceof ContentComponentFactoryInterface) {
            throw new \InvalidArgumentException(
                'Content component factory ' . $contentComponentFactoryClassName
                . ' does not implement the required ' . ContentComponentFactoryInterface::class,
                1656762672
            );
        }

        return $contentComponentFactory;
    }
}
