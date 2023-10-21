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
    public function getAugmenterAttributesForContentNode(Node $contentNode, ?RenderingEntryPoint $renderingEntryPoint = null): ?array
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentNode->subgraphIdentity->contentRepositoryId);
        $renderingEntryPoint ??= RenderingEntryPoint::forContentRendererDelegation();

        $nodeAddress = NodeAddressFactory::create($contentRepository)->createFromNode($contentNode);

        $attributes['data-__neos-fusion-path'] = $renderingEntryPoint->serializeForNeosUi();
        $attributes['data-__neos-node-contextpath'] = $nodeAddress->serializeForUri();
        if (
            $contentRepository->getNodeTypeManager()->getNodeType($contentNode->nodeTypeName)
                ->isOfType(NodeTypeNameFactory::NAME_CONTENT_COLLECTION)
        ) {
            $attributes['class'] = 'neos-contentcollection';
        }

        return $attributes;
    }

    public function getScriptForContentNode(Node $contentNode): string
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentNode->subgraphIdentity->contentRepositoryId);
        $nodeAddress = NodeAddressFactory::create($contentRepository)->createFromNode($contentNode);
        $serializedNode = json_encode($this->nodeInfoHelper->renderNode($contentNode));

        return "<script data-neos-nodedata>(function(){(this['@Neos.Neos.Ui:Nodes'] = this['@Neos.Neos.Ui:Nodes'] || {})['{$nodeAddress->serializeForUri()}'] = {$serializedNode}})()</script>";
    }

    /**
     * @return array<string,mixed>
     */
    public function forDocumentNode(Node $documentNode, ?string $locator = null, ?Node $siteNode = null): ?array
    {
        $contentRepository = $this->contentRepositoryRegistry->get($documentNode->subgraphIdentity->contentRepositoryId);
        if (!$this->needsMetadata($documentNode, $contentRepository, true)) {
            return null;
        }
        $locator = is_string($locator) ? $locator : '/<Neos.Neos:Document>/' . $documentNode->nodeAggregateId->value;

        $nodeAddress = NodeAddressFactory::create($contentRepository)->createFromNode($documentNode);
        \Neos\Flow\var_dump($nodeAddress);
        $metadata['data-__neos-node-contextpath'] = $nodeAddress->serializeForUri();
        $metadata['data-__neos-fusion-path'] = $locator;
        $metadata = $this->addGenericEditingMetadata($metadata, $documentNode);
        $metadata = $this->addNodePropertyAttributes($metadata, $documentNode);
        $metadata = $this->addDocumentMetadata($contentRepository, $metadata, $documentNode, $siteNode);
        $metadata = $this->addCssClasses($metadata, $documentNode);

        return $metadata;
    }
}
