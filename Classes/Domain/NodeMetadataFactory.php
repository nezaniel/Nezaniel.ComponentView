<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Domain;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Service\ContentElementWrappingService;
use Neos\Neos\Ui\Fusion\Helper\NodeInfoHelper;

/**
 * The domain service to create node metadata
 */
#[Flow\Scope('singleton')]
final class NodeMetadataFactory extends ContentElementWrappingService
{
    #[Flow\Inject]
    protected NodeInfoHelper $nodeInfoHelper;

    #[Flow\Inject]
    protected UriService $uriService;

    /**
     * @return array<string,mixed>
     */
    public function getAugmenterAttributesForContentNode(NodeInterface $contentNode, ?string $locator = null): ?array
    {
        if (!$this->needsMetadata($contentNode, false)) {
            return null;
        }
        $locator = is_string($locator) ? $locator : '/<Neos.Neos:Content>/' . $contentNode->getIdentifier();

        $metadata['data-__neos-node-contextpath'] = $contentNode->getContextPath();
        $metadata['data-__neos-fusion-path'] = $locator;
        $metadata['tabindex'] = 0;
        $metadata = $this->addGenericEditingMetadata($metadata, $contentNode);
        $metadata = $this->addNodePropertyAttributes($metadata, $contentNode);
        $metadata = $this->addCssClasses($metadata, $contentNode, $this->collectEditingClassNames($contentNode));

        return $metadata;
    }

    public function getScriptForContentNode(NodeInterface $contentNode): string
    {
        $metadata = json_encode($this->nodeInfoHelper->renderNodeWithPropertiesAndChildrenInformation(
            $contentNode,
            $this->uriService->getControllerContext()
        ));

        return "<script data-neos-nodedata>(function(){(this['@Neos.Neos.Ui:Nodes'] = this['@Neos.Neos.Ui:Nodes'] || {})['" .  $contentNode->getContextPath() . "'] = {$metadata}})()</script>";
    }

    /**
     * @return array<string,mixed>
     */
    public function forDocumentNode(NodeInterface $documentNode, ?string $locator = null): ?array
    {
        if (!$this->needsMetadata($documentNode, true)) {
            return null;
        }
        $locator = is_string($locator) ? $locator : '/<Neos.Neos:Document>/' . $documentNode->getIdentifier();

        $metadata['data-__neos-node-contextpath'] = $documentNode->getContextPath();
        $metadata['data-__neos-fusion-path'] = $locator;
        $metadata = $this->addGenericEditingMetadata($metadata, $documentNode);
        $metadata = $this->addNodePropertyAttributes($metadata, $documentNode);
        $metadata = $this->addDocumentMetadata($metadata, $documentNode);
        $metadata = $this->addCssClasses($metadata, $documentNode, []);

        return $metadata;
    }
}
