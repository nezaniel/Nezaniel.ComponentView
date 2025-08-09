<?php

declare(strict_types=1);

namespace Nezaniel\ComponentView\Application;

use PackageFactory\Neos\ComponentEngine\ComponentInterface;

/**
 * The interface to be implemented by page factories
 */
interface PageFactoryInterface
{
    public function forDocumentNode(ComponentViewRuntimeVariables $runtimeVariables): ComponentInterface;
}
