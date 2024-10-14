<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Domain;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\FrontendRouting\NodeAddressFactory;
use Neos\Neos\Service\ContentElementWrappingService;

/**
 * The domain service to create node metadata
 */
#[Flow\Scope('singleton')]
final class NodeMetadataFactory extends ContentElementWrappingService
{
    /**
     * @return array<string,mixed>|null
     */
    public function getAugmenterAttributesForContentNode(
        Node $contentNode,
        ?RenderingEntryPoint $renderingEntryPoint = null,
        ?string $additionalClasses = null
    ): ?array {
        $contentRepository = $this->contentRepositoryRegistry->get($contentNode->contentRepositoryId);
        $renderingEntryPoint ??= RenderingEntryPoint::forContentRendererDelegation();

        $nodeAddress = NodeAddressFactory::create($contentRepository)->createFromNode($contentNode);

        $attributes['data-__neos-fusion-path'] = $renderingEntryPoint->serializeForNeosUi($contentNode);
        $attributes['data-__neos-node-contextpath'] = $nodeAddress->serializeForUri();
        if (
            $contentRepository->getNodeTypeManager()->getNodeType($contentNode->nodeTypeName)
                ?->isOfType(NodeTypeNameFactory::NAME_CONTENT_COLLECTION)
        ) {
            $attributes['class'] = 'neos-contentcollection ' . $additionalClasses;
        } elseif ($additionalClasses) {
            $attributes['class'] = $additionalClasses;
        }

        return $attributes;
    }

    /**
     * @return array<string,mixed>
     */
    public function forDocumentNode(Node $documentNode, ?string $locator = null, ?Node $siteNode = null): ?array
    {
        $contentRepository = $this->contentRepositoryRegistry->get($documentNode->contentRepositoryId);
        $locator = is_string($locator) ? $locator : '/<Neos.Neos:Document>/' . $documentNode->aggregateId->value;

        $nodeAddress = NodeAddressFactory::create($contentRepository)->createFromNode($documentNode);
        $metadata['data-__neos-node-contextpath'] = $nodeAddress->serializeForUri();
        $metadata['data-__neos-fusion-path'] = $locator;

        return $metadata;
    }
}
