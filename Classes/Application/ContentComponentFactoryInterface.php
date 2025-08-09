<?php

declare(strict_types=1);

namespace Nezaniel\ComponentView\Application;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use PackageFactory\Neos\ComponentEngine\ComponentInterface;

/**
 * The interface to be implemented by content component factories
 */
interface ContentComponentFactoryInterface
{
    public function forContentNode(
        Node $contentNode,
        ComponentViewRuntimeVariables $runtimeVariables,
        CacheTagSet &$cacheTags
    ): ComponentInterface;
}
