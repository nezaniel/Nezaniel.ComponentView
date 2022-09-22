<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Application;

use Neos\ContentRepository\Domain\Model\Node;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\View\ViewInterface;

/**
 * A view that triggers creation of self-rendering components and lets them render themselves
 */
#[Flow\Proxy(false)]
class ComponentView implements ViewInterface
{
    private ?Node $documentNode = null;

    private ?ControllerContext $controllerContext = null;

    public function setControllerContext(ControllerContext $controllerContext)
    {
        $this->controllerContext = $controllerContext;
    }

    public function assign($key, $value): void
    {
        if ($key === 'value' && $value instanceof Node) {
            $this->documentNode = $value;
        }
    }

    public function assignMultiple(array $values): void
    {
    }

    public function canRender(ControllerContext $controllerContext): bool
    {
        return $this->documentNode instanceof Node;
    }

    public function render(): string
    {
        $factoryStart = microtime(true);

        $pageFactoryRelay = new PageFactoryRelay();
        $page = $pageFactoryRelay->delegate(
            $this->documentNode,
            $this->documentNode->getContext()->getCurrentSiteNode(),
            $this->documentNode->getContext(),
            $this->documentNode->getContext()->isInBackend(),
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

    public static function createWithOptions(array $options): self
    {
        return new self();
    }
}
