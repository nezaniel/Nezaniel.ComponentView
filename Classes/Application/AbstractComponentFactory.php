<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Application;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\NodeLabel\NodeLabelGeneratorInterface;
use Neos\Neos\Service\ContentElementEditableService;
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
    protected ComponentCache $componentCache;

    #[Flow\Inject]
    protected ContentElementEditableService $contentElementEditableService;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected NodeLabelGeneratorInterface $nodeLabelGenerator;

    final protected function getEditableProperty(Node $node, string $propertyName, bool $block = false): string
    {
        return $this->contentElementEditableService->wrapContentProperty(
            $node,
            $propertyName,
            ($block ? '<div>' : '')
            . ($node->getProperty($propertyName) ?: '')
            . ($block ? '</div>' : '')
        );
    }

    final protected function getNodeType(Node $node): ?NodeType
    {
        return $this->contentRepositoryRegistry->get($node->contentRepositoryId)
            ->getNodeTypeManager()
            ->getNodeType($node->nodeTypeName);
    }

    final protected function getNodeLabel(Node $node): string
    {
        return $this->nodeLabelGenerator->getLabel($node);
    }
}
