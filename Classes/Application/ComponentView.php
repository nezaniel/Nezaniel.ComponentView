<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Application;

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindAncestorNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\View\AbstractView;
use Nezaniel\ComponentView\Domain\UriService;

/**
 * A view that triggers creation of self-rendering components and lets them render themselves
 */
class ComponentView extends AbstractView
{
    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected UriService $uriService;

    private ?Node $documentNode = null;

    public function setControllerContext(ControllerContext $controllerContext): void
    {
        $this->controllerContext = $controllerContext;
        $this->uriService->setControllerContext($controllerContext);
    }

    public function assign($key, $value): void
    {
        if ($key === 'value' && $value instanceof Node) {
            $this->documentNode = $value;
        }
    }

    public function assignMultiple(array $values): void
    {
        foreach ($values as $key => $value) {
            if ($key === 'value' && $value instanceof Node) {
                $this->documentNode = $value;
            }
        }
    }

    public function canRender(ControllerContext $controllerContext): bool
    {
        return $this->documentNode instanceof Node;
    }

    public function render(): string
    {
        $factoryStart = microtime(true);

        $inBackend = match($this->controllerContext->getRequest()->getControllerActionName()) {
            'show' => false,
            'preview' => true,
            default => throw new \InvalidArgumentException('unknown action ' . $this->controllerContext->getRequest()->getControllerActionName())
        };

        $contentRepository = $this->contentRepositoryRegistry->get($this->documentNode->subgraphIdentity->contentRepositoryId);
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($this->documentNode);
        $siteNode = $this->documentNode;
        while ($siteNode && !$siteNode->nodeType->isOfType('Neos.Neos:Site')) {
            $siteNode = $subgraph->findParentNode($siteNode->nodeAggregateId);
        }
        /*
        $siteNode = $contentRepository->getNodeTypeManager()->getNodeType($this->documentNode->nodeTypeName)
            ->isOfType('Neos.Neos:Site')
            ? $this->documentNode
            : $subgraph->findAncestorNodes(
                $this->documentNode->nodeAggregateId,
                FindAncestorNodesFilter::create(
                    nodeTypeConstraints: 'Neos.Neos:Site'
                )
            )->first();*/
        assert($siteNode instanceof Node);

        $pageFactoryRelay = new PageFactoryRelay();
        $page = $pageFactoryRelay->delegate(new ComponentViewRuntimeVariables(
            $siteNode,
            $this->documentNode,
            $subgraph,
            $this->controllerContext->getRequest(),
            $inBackend
        ));
        $factoryTime = microtime(true) - $factoryStart;
        $renderingStart = microtime(true);
        $result = $page->render();
        $renderingTime = microtime(true) - $renderingStart;
        $result .= '<!-- Factory: ' . $factoryTime . ', Rendering: ' . $renderingTime . '-->';

        return $result;
    }

    public function canRenderWithNodeAndPath(): bool
    {
        return true;
    }
}
