<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Application;

use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindClosestNodeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\View\AbstractView;
use Neos\Neos\Domain\Model\RenderingMode;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\Domain\Service\RenderingModeService;
use Neos\Neos\Ui\View\OutOfBandRenderingCapable;
use Nezaniel\ComponentView\Domain\RenderingEntryPoint;
use Nezaniel\ComponentView\Domain\UriService;

/**
 * A view that triggers creation of self-rendering components and lets them render themselves
 */
class ComponentView extends AbstractView implements OutOfBandRenderingCapable
{
    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected UriService $uriService;

    #[Flow\Inject]
    protected RenderingModeService $renderingModeService;

    private ?Node $documentNode = null;

    private ?Node $node = null;

    private ?RenderingEntryPoint $renderingEntryPoint = null;

    /**
     * @var array<string,mixed>
     */
    protected $supportedOptions = [
        'renderingModeName' => [
            RenderingMode::FRONTEND,
            'Name of the user interface mode to use',
            'string'
        ]
    ];

    public function setControllerContext(ControllerContext $controllerContext): void
    {
        $this->controllerContext = $controllerContext;
        $this->uriService->setControllerContext($controllerContext);
    }

    public function assign($key, $value): self
    {
        if ($key === 'value' && $value instanceof Node) {
            $this->node = $value;
            $subgraph = $this->contentRepositoryRegistry->subgraphForNode($this->node);
            $this->documentNode = $this->findClosestDocument($subgraph, $value);
        }

        return $this;
    }

    /**
     * @param array<string,mixed> $values
     */
    public function assignMultiple(array $values): self
    {
        foreach ($values as $key => $value) {
            if ($key === 'value' && $value instanceof Node) {
                $this->node = $value;
                $subgraph = $this->contentRepositoryRegistry->subgraphForNode($this->node);
                $this->documentNode = $this->findClosestDocument($subgraph, $value);
            }
        }

        return $this;
    }

    public function canRender(ControllerContext $controllerContext): bool
    {
        return $this->documentNode instanceof Node;
    }

    public function render(): string
    {
        assert($this->documentNode instanceof Node);
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($this->documentNode);
        $siteNode = $this->findClosestSite($subgraph, $this->documentNode);
        assert($siteNode instanceof Node);
        assert($this->node instanceof Node);
        /** @var string $renderingModeName */
        $renderingModeName = $this->getOption('renderingModeName');

        $runtimeVariables = new ComponentViewRuntimeVariables(
            $siteNode,
            $this->documentNode,
            $subgraph,
            $this->controllerContext->getRequest(),
            $this->renderingModeService->findByName($renderingModeName)
        );
        if ($this->renderingEntryPoint) {
            $factory = (new $this->renderingEntryPoint->className());
            if ($this->renderingEntryPoint->isContentRendererDelegation()) {
                /** @var ContentRenderer $factory */
                $nodeType = $this->contentRepositoryRegistry->get($this->node->subgraphIdentity->contentRepositoryId)
                    ->getNodeTypeManager()->getNodeType($this->node->nodeTypeName);
                if ($nodeType->isOfType(NodeTypeNameFactory::NAME_CONTENT_COLLECTION)) {
                    $component = $factory->forContentCollection($this->node, $runtimeVariables);
                } else {
                    $cacheTags = new CacheTagSet();
                    $component = $factory->delegate($this->node, $runtimeVariables, $cacheTags);
                }
            } else {
                $component = $factory->{$runtimeVariables};
            }
        } else {
            $pageFactoryRelay = new PageFactoryRelay();
            $component = $pageFactoryRelay->delegate($runtimeVariables);
        }

        return $component->render();
    }

    private function findClosestDocument(ContentSubgraphInterface $subgraph, Node $node): ?Node
    {
        $nodeType = $this->contentRepositoryRegistry->get($subgraph->getIdentity()->contentRepositoryId)
            ->getNodeTypeManager()->getNodeType($node->nodeTypeName);
        if ($nodeType->isOfType(NodeTypeNameFactory::NAME_DOCUMENT)) {
            return $node;
        }
        return $subgraph->findClosestNode(
            $node->nodeAggregateId,
            FindClosestNodeFilter::create(nodeTypes: NodeTypeNameFactory::NAME_DOCUMENT)
        );
    }

    private function findClosestSite(ContentSubgraphInterface $subgraph, Node $node): ?Node
    {
        $nodeType = $this->contentRepositoryRegistry->get($subgraph->getIdentity()->contentRepositoryId)
            ->getNodeTypeManager()->getNodeType($node->nodeTypeName);
        if ($nodeType->isOfType(NodeTypeNameFactory::NAME_SITE)) {
            return $node;
        }
        return $subgraph->findClosestNode(
            $node->nodeAggregateId,
            FindClosestNodeFilter::create(nodeTypes: NodeTypeNameFactory::NAME_SITE)
        );
    }

    public function canRenderWithNodeAndPath(): bool
    {
        return true;
    }

    public function setRenderingEntryPoint(string $renderingEntryPoint): void
    {
        $this->renderingEntryPoint = RenderingEntryPoint::fromNeosUiString($renderingEntryPoint);
    }
}
