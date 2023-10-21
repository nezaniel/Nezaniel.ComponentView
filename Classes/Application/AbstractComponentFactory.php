<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Application;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
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
}
