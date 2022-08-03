<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Domain;

use Neos\Flow\Annotations as Flow;

/**
 * A rendering entry point used to restart the rendering process on a nested cache miss
 */
#[Flow\Proxy(false)]
final class RenderingEntryPoint
{
    private const SEPARATOR = '::';

    public function __construct(
        public readonly string $className,
        public readonly string $methodName
    ) {
    }

    public static function fromString(string $string): self
    {
        list($className, $methodName) = explode(self::SEPARATOR, $string);
        return new self($className, $methodName);
    }

    public function canResolve(): bool
    {
        return class_exists($this->className) && method_exists($this->className, $this->methodName);
    }

    public function serializeForCache(): string
    {
        return $this->className . self::SEPARATOR . $this->methodName;
    }
}
