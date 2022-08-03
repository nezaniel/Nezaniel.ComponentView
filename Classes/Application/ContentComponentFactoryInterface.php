<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Application;

use Neos\ContentRepository\Domain\Model\Node;
use Neos\Neos\Domain\Service\ContentContext;
use Nezaniel\ComponentView\Domain\ComponentInterface;

/**
 * The interface to be implemented by content component factories
 */
interface ContentComponentFactoryInterface
{
    public function forContentNode(Node $contentNode, ContentContext $subgraph, bool $inBackend, CacheTags &$cacheTags): ComponentInterface;
}
