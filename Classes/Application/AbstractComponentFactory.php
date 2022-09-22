<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Application;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraintFactory;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodes;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Service\ContentElementEditableService;
use Nezaniel\ComponentView\Domain\CacheDirective;
use Nezaniel\ComponentView\Domain\CacheSegment;
use Nezaniel\ComponentView\Domain\ComponentInterface;
use Nezaniel\ComponentView\Domain\RenderingEntryPoint;
use Nezaniel\ComponentView\Domain\UriService;

/**
 * The abstract factory for creating components
 */
#[Flow\Scope('singleton')]
abstract class AbstractComponentFactory
{
    #[Flow\Inject]
    protected UriService $uriService;

    #[Flow\Inject]
    protected NodeTypeConstraintFactory $nodeTypeConstraintFactory;

    #[Flow\Inject]
    protected ComponentCache $componentCache;

    #[Flow\Inject]
    protected ContentElementEditableService $contentElementEditableService;

    /**
     * @return UriService
     */
    public function getUriService(): UriService
    {
        return $this->uriService;
    }

    /**
     * @return TraversableNodes<int,TraversableNodeInterface>
     */
    final protected function findChildNodesByNodeTypeFilterString(
        TraversableNodeInterface $parentNode,
        string $nodeTypeFilterString
    ): TraversableNodes {
        return $parentNode->findChildNodes($this->nodeTypeConstraintFactory->parseFilterString($nodeTypeFilterString));
    }

    final protected function getEditableProperty(TraversableNodeInterface $node, string $propertyName, bool $block = false): string
    {
        /** @var NodeInterface $node */
        return $this->contentElementEditableService->wrapContentProperty(
            $node,
            $propertyName,
            ($block ? '<div>' : '')
            . ($node->getProperty($propertyName) ?: '')
            . ($block ? '</div>' : '')
        );
    }
}
