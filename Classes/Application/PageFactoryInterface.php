<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Application;

use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Mvc\ActionRequest;
use Nezaniel\ComponentView\Domain\ComponentInterface;

/**
 * The interface to be implemented by page factories
 */
interface PageFactoryInterface
{
    public function forDocumentNode(
        Node $documentNode,
        Node $site,
        ContentSubgraphInterface $subgraph,
        bool $inBackend,
        ActionRequest $request
    ): ComponentInterface;
}
