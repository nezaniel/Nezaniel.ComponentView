<?php

declare(strict_types=1);

namespace Nezaniel\ComponentView\Domain;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
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

        $nodeAddress = NodeAddress::fromNode($contentNode);

        $attributes['data-__neos-fusion-path'] = $renderingEntryPoint->serializeForNeosUi($contentNode);
        $attributes['data-__neos-node-contextpath'] = $nodeAddress->toJson();
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

    public function getScriptForContentNode(Node $contentNode): string
    {
        $nodeAddress = NodeAddress::fromNode($contentNode);

        // TODO illegal dependency on ui
        $serializedNode = json_encode($this->nodeInfoHelper->renderNodeWithPropertiesAndChildrenInformation($contentNode));

        return "<script data-neos-nodedata>(function(){(this['@Neos.Neos.Ui:Nodes'] = this['@Neos.Neos.Ui:Nodes'] || {})['{$nodeAddress->toJson()}'] = {$serializedNode}})()</script>";
    }

    /**
     * @return array<string,mixed>
     */
    public function forDocumentNode(Node $documentNode, ?string $locator = null, ?Node $siteNode = null): ?array
    {
        $locator = is_string($locator) ? $locator : '/<Neos.Neos:Document>/' . $documentNode->aggregateId->value;

        $nodeAddress = NodeAddress::fromNode($documentNode);
        $metadata['data-__neos-node-contextpath'] = $nodeAddress->toJson();
        $metadata['data-__neos-fusion-path'] = $locator;

        return $metadata;
    }
}
