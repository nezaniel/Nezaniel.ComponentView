<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Application;

use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Neos\Domain\Model\RenderingMode;

/**
 * Runtime variables for the component view
 */
#[Flow\Proxy(false)]
final readonly class ComponentViewRuntimeVariables
{
    public function __construct(
        public Node $siteNode,
        public Node $documentNode,
        public ContentSubgraphInterface $subgraph,
        public ActionRequest $request,
        public RenderingMode $renderingMode
    ) {
    }

    public function with(
        Node $siteNode = null,
        Node $documentNode = null,
        ContentSubgraphInterface $subgraph = null,
        ActionRequest $request = null,
        RenderingMode $renderingMode = null,
    ): self {
        return new self(
            $siteNode ?: $this->siteNode,
            $documentNode ?: $this->documentNode,
            $subgraph ?: $this->subgraph,
            $request ?: $this->request,
            $renderingMode ?: $this->renderingMode,
        );
    }
}
