<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Application;

use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\ContentContext;
use Nezaniel\ComponentView\Domain\ComponentInterface;
use Nezaniel\ComponentView\Domain\CacheDirective;
use Nezaniel\ComponentView\Domain\CacheSegment;
use Nezaniel\ComponentView\Domain\ComponentCollection;
use PackageFactory\AtomicFusion\PresentationObjects\Fusion\AbstractComponentPresentationObjectFactory;

/**
 * The content renderer application service
 */
#[Flow\Scope("singleton")]
final class ContentRenderer extends AbstractComponentPresentationObjectFactory
{
    #[Flow\Inject]
    protected NodeMetadataWrapperFactory $nodeMetadataWrapperFactory;

    #[Flow\Inject]
    protected ComponentCache $componentCache;

    public function forContentCollectionChildNode(
        Node $documentNode,
        NodeName $collectionName,
        ContentContext $subgraph,
        bool $inBackend
    ): CacheSegment {
        return $this->resolveCacheSegmentForContentCollection(
            $documentNode,
            $collectionName,
            $subgraph,
            $inBackend
        );
    }

    public function forContentCollection(
        Node $contentCollection,
        ContentContext $subgraph,
        bool $inBackend
    ): CacheSegment {
        return $this->resolveCacheSegmentForContentCollection(
            $contentCollection,
            null,
            $subgraph,
            $inBackend
        );
    }

    private function resolveCacheSegmentForContentCollection(
        Node $node,
        ?NodeName $collectionName,
        ContentContext $subgraph,
        bool $inBackend
    ): CacheSegment {
        $workspaceName = $node->getContext()->getWorkspace()->getName();
        $cacheEntryIdentifier = 'node_' . $node->getNodeAggregateIdentifier()
            . '_' . $workspaceName . '_' . $inBackend . ($collectionName ? '_' . $collectionName : '');

        $component = $this->componentCache->findComponent($cacheEntryIdentifier, $subgraph, $inBackend);

        if (is_null($component)) {
            /** @var Node $contentCollection */
            $contentCollection = $collectionName ? $node->findNamedChildNode($collectionName) : $node;
            $cacheTags = new CacheTags(
                CacheTag::forAncestorNode($contentCollection, $workspaceName),
                CacheTag::forNode($contentCollection, $workspaceName)
            );
            $content = new ComponentCollection(... array_map(
                function (Node $childNode) use ($subgraph, $inBackend, &$cacheTags): ComponentInterface {
                    return $this->delegate($childNode, $subgraph, $inBackend, $cacheTags);
                },
                $contentCollection->findChildNodes()->toArray()
            ));
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
                $node->getNodeAggregateIdentifier(),
                $collectionName,
                null
            ),
            $component
        );
    }

    public function delegate(Node $contentNode, ContentContext $subgraph, bool $inBackend, CacheTags &$cacheTags): ComponentInterface
    {
        $contentComponentFactory = $this->resolveContentComponentFactory($contentNode);
        $component = $contentComponentFactory->forContentNode($contentNode, $subgraph, $inBackend, $cacheTags);

        return $inBackend
            ? $this->nodeMetadataWrapperFactory->forNode($contentNode, $component)
            : $component;
    }

    private function resolveContentComponentFactory(Node $contentNode): ContentComponentFactoryInterface
    {
        list($packageKey, $contentName) = explode(':', $contentNode->getNodeTypeName()->getValue());

        $contentComponentFactoryClassName = \str_replace('.', '\\', $packageKey) . '\\Integration\\ContentComponentFactory';
        if (!class_exists($contentComponentFactoryClassName)) {
            throw new \InvalidArgumentException('Missing content slot factory in package ' . $packageKey, 1656762670);
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
