<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Application;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\View\AbstractView;
use Neos\Neos\Domain\Service\SiteNodeUtility;
use Nezaniel\ComponentView\Domain\UriService;

/**
 * A view that triggers creation of self-rendering components and lets them render themselves
 */
class ComponentView extends AbstractView
{
    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * @Flow\Inject
     * @var SiteNodeUtility
     */
    protected $siteNodeUtility;

    /**
     * @Flow\Inject
     * @var UriService
     */
    protected $uriService;

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

        $pageFactoryRelay = new PageFactoryRelay();
        $page = $pageFactoryRelay->delegate(
            $this->documentNode,
            $this->siteNodeUtility->findSiteNode($this->documentNode),
            $this->contentRepositoryRegistry->subgraphForNode($this->documentNode),
            $inBackend,
            $this->controllerContext->getRequest()
        );
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
