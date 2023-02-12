<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Application;

use Neos\ContentRepository\Domain\Model\Node;
use Neos\Flow\Annotations as Flow;
use Nezaniel\ComponentView\Domain\ComponentCollection;
use Nezaniel\ComponentView\Domain\ComponentInterface;
use Nezaniel\ComponentView\Domain\NodeMetadataFactory;
use Nezaniel\ComponentView\Domain\NodeMetadataWrapper;

/**
 * The factory application service for creating node metadata wrapper components
 */
#[Flow\Scope('singleton')]
final class NodeMetadataWrapperFactory
{
    public function __construct(
        private NodeMetadataFactory $nodeMetadataFactory
    ) {
    }

    public function forNode(Node $node, ComponentInterface|string $content): NodeMetadataWrapper
    {
        return new NodeMetadataWrapper(
            $this->nodeMetadataFactory->getAugmenterAttributesForContentNode($node),
            $content,
            $this->nodeMetadataFactory->getScriptForContentNode($node)
        );
    }
}
