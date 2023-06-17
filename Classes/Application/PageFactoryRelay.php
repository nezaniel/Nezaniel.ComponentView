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
 * The page factory relay application service
 */
final class PageFactoryRelay
{
    public function delegate(
        Node $documentNode,
        Node $site,
        ContentSubgraphInterface $subgraph,
        bool $inBackend,
        ActionRequest $request
    ): ComponentInterface {
        $pageFactory = $this->resolvePageFactory($documentNode);

        return $pageFactory->forDocumentNode($documentNode, $site, $subgraph, $inBackend, $request);
    }

    private function resolvePageFactory(Node $documentNode): PageFactoryInterface
    {
        list($packageKey, $documentName) = explode(':', $documentNode->nodeTypeName->value);

        $pageFactoryClassName = \str_replace('.', '\\', $packageKey) . '\\Integration\\PageFactory';
        if (!class_exists($pageFactoryClassName)) {
            throw new \InvalidArgumentException('Missing page factory in package ' . $packageKey, 1656762188);
        }
        $pageFactory = new $pageFactoryClassName();
        if (!$pageFactory instanceof PageFactoryInterface) {
            throw new \InvalidArgumentException(
                'Page factory ' . $pageFactoryClassName
                    . ' does not implement the required ' . PageFactoryInterface::class,
                1656762188
            );
        }

        return $pageFactory;
    }
}
