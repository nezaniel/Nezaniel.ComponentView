<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Infrastructure;

use Neos\Flow\Annotations as Flow;

/**
 * The specification to determine whether a component class is a collection type
 */
#[Flow\Proxy(false)]
final class IsCollectionType
{
    public static function isSatisfiedByReflectionClass(\ReflectionClass $reflectionClass): bool
    {
        $reflectionProperties = $reflectionClass->getProperties();

        return count($reflectionProperties) === 1
            && $reflectionProperties[0]->getType()?->getName() === 'array';
    }
}
