<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Domain;

use Neos\Flow\Annotations as Flow;
use Nezaniel\ComponentView\Application\ContentRenderer;

/**
 * A rendering entry point used to restart the rendering process on a nested cache miss
 */
#[Flow\Proxy(false)]
final class RenderingEntryPoint
{
    private const SEPARATOR = '::';

    private const DELEGATION_DESIGNATOR = '--DELEGATE--';

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

    public static function fromNeosUiString(string $string): self
    {
        list($className, $methodName) = explode('/', $string);
        $className = \str_replace('.', '\\', $className);

        return new self($className, $methodName);
    }

    public static function forContentRendererDelegation(): self
    {
        return new self(
            ContentRenderer::class,
            self::DELEGATION_DESIGNATOR
        );
    }

    public function isContentRendererDelegation(): bool
    {
        return $this->className === ContentRenderer::class
            && $this->methodName === self::DELEGATION_DESIGNATOR;
    }

    public static function fromMethod(string $method): self
    {
        return self::fromString(\str_replace('_Original::', '::', $method));
    }

    public function canResolve(): bool
    {
        return class_exists($this->className) && method_exists($this->className, $this->methodName);
    }

    public function serializeForCache(): string
    {
        return $this->className . self::SEPARATOR . $this->methodName;
    }

    public function serializeForNeosUi(): string
    {
        return \str_replace('\\', '.', $this->className) .'/' . $this->methodName;
    }
}
