<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Application;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;
use Nezaniel\ComponentView\Domain\ComponentInterface;
use Nezaniel\ComponentView\Domain\NodeMetadataFactory;
use Nezaniel\ComponentView\Domain\NodeMetadataWrapper;

/**
 * The factory application service for creating node metadata wrapper components
 */
#[Flow\Scope('singleton')]
final readonly class NodeMetadataWrapperFactory
{
    public function __construct(
        private NodeMetadataFactory $nodeMetadataFactory
    ) {
    }

    public function forNode(Node $node, ComponentInterface|string $content, ?string $additionalClasses = null): NodeMetadataWrapper
    {
        return new NodeMetadataWrapper(
            $this->nodeMetadataFactory->getAugmenterAttributesForContentNode(contentNode: $node, additionalClasses: $additionalClasses),
            $content,
        );
    }
}
