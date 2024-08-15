<?php

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

    /**
     * @template T
     * @param class-string<T> $expectedType
     * @return ?T
     */
    final protected function getObjectValue(Node $node, string $propertyName, string $expectedType): mixed
    {
        $propertyValue = $node->getProperty($propertyName);

        return $propertyValue instanceof $expectedType
            ? $propertyValue
            : null;
    }

    /**
     * @template T
     * @param class-string<T> $expectedType
     * @return array<int,T>|null
     */
    final protected function getObjectArrayValue(Node $node, string $propertyName, string $expectedType): ?array
    {
        $propertyValue = $node->getProperty($propertyName);
        if (!is_array($propertyValue)) {
            return null;
        }
        return array_filter(
            $propertyValue,
            fn (mixed $item): bool => $item instanceof $expectedType
        );
    }

    final protected function getStringValue(Node $node, string $propertyName): ?string
    {
        $propertyValue = $node->getProperty($propertyName);

        return is_string($propertyValue) ? $propertyValue : null;
    }

    final protected function getBoolValue(Node $node, string $propertyName): ?bool
    {
        $propertyValue = $node->getProperty($propertyName);

        return is_bool($propertyValue) ? $propertyValue : null;
    }

    final protected function getIntValue(Node $node, string $propertyName): ?int
    {
        $propertyValue = $node->getProperty($propertyName);

        return is_int($propertyValue) ? $propertyValue : null;
    }

    final protected function getFloatValue(Node $node, string $propertyName): ?float
    {
        $propertyValue = $node->getProperty($propertyName);

        return is_float($propertyValue) ? $propertyValue : null;
    }
}
