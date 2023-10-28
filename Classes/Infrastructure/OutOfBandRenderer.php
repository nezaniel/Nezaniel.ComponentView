<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Infrastructure;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Neos\Ui\Domain\Model\Feedback\Operations\RenderContentOutOfBand;
use Nezaniel\ComponentView\Application\ComponentView;
use Psr\Http\Message\ResponseInterface;

#[Flow\Scope('singleton')]
#[Flow\Aspect]
final class OutOfBandRenderer extends RenderContentOutOfBand
{
    #[Flow\Around('method(Neos\Neos\Ui\Domain\Model\Feedback\Operations\RenderContentOutOfBand->renderContent())')]
    public function renderContent(ControllerContext $controllerContext): string|ResponseInterface
    {
        if (is_null($this->node)) {
            return '';
        }
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($this->node);
        $parentNode = $subgraph->findParentNode($this->node->nodeAggregateId);
        if ($parentNode) {
            $cacheTags = $this->cachingHelper->nodeTag($parentNode);
            foreach ($cacheTags as $tag) {
                $this->contentCache->flushByTag($tag);
            }
            $parentDomAddress = $this->getParentDomAddress();
            if ($parentDomAddress) {
                $renderingMode = $this->renderingModeService->findByCurrentUser();

                $view = new ComponentView();
                $view->setControllerContext($controllerContext);
                $view->setOption('renderingModeName', $renderingMode->name);

                $view->assign('value', $parentNode);
                $view->setRenderingEntryPoint($parentDomAddress->getFusionPath());

                return $view->render();
            }
        }

        return '';
    }
}
