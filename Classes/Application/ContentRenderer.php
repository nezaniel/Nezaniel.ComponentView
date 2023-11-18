<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Application;

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
        Node $node,
        NodeName $collectionName,
        ComponentViewRuntimeVariables $runtimeVariables,
    ): CacheSegment {
        return $this->resolveCacheSegmentForContentCollection(
            $node,
            $collectionName,
            $runtimeVariables,
        );
    }

    public function forContentCollection(
        Node $contentCollection,
        ComponentViewRuntimeVariables $runtimeVariables
    ): CacheSegment {
        return $this->resolveCacheSegmentForContentCollection(
            $contentCollection,
            null,
            $runtimeVariables,
        );
    }

    private function resolveCacheSegmentForContentCollection(
        Node $node,
        ?NodeName $collectionName,
        ComponentViewRuntimeVariables $runtimeVariables,
    ): CacheSegment {
        $cacheEntryIdentifier = 'node_' . $node->nodeAggregateId->value
            . '_' . $runtimeVariables->documentNode->subgraphIdentity->contentStreamId->value
            . '_' . $runtimeVariables->renderingMode->name . ($collectionName ? '_' . $collectionName->value : '');

        $component = $this->componentCache->findComponent(
            $cacheEntryIdentifier,
            $runtimeVariables
        );

        if (is_null($component)) {
            $contentCollection = $collectionName
                ? $runtimeVariables->subgraph->findChildNodeConnectedThroughEdgeName(
                    $node->nodeAggregateId,
                    $collectionName
                ) : $node;
            assert($contentCollection instanceof Node);
            $cacheTags = new CacheTagSet(
                CacheTag::forAncestorNodeFromNode($contentCollection),
                CacheTag::forNodeAggregateFromNode($contentCollection)
            );
            $content = new ComponentCollection(... array_map(
                // We can't use an arrow function here because we need to modify $cacheTags
                function (Node $childNode) use($runtimeVariables, &$cacheTags): ComponentInterface {
                    return $this->delegate(
                        $childNode,
                        $runtimeVariables,
                        $cacheTags
                    );
                },
                iterator_to_array($runtimeVariables->subgraph->findChildNodes(
                    $contentCollection->nodeAggregateId,
                    FindChildNodesFilter::create()
                ))
            ));
            if ($content->isEmpty()) {
                $content = new ComponentCollection('<span></span>');
            }

            $component = $runtimeVariables->renderingMode->isEdit
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
        ComponentViewRuntimeVariables $runtimeVariables,
        CacheTagSet &$cacheTags
    ): ComponentInterface {
        $contentComponentFactory = $this->resolveContentComponentFactory($contentNode);
        $component = $contentComponentFactory->forContentNode(
            $contentNode,
            $runtimeVariables,
            $cacheTags
        );

        return $runtimeVariables->renderingMode->isEdit
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
