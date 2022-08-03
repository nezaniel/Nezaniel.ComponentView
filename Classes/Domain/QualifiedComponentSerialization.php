<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Domain;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
/**
 * @deprecated
 */
final class QualifiedComponentSerialization implements \JsonSerializable
{
    public function __construct(
        private readonly ComponentInterface|string|null $component
    ) {
    }

    public static function create(ComponentInterface|string|null $component): self
    {
        return new self($component);
    }

    /**
     * @return array<string,mixed>|string|null
     */
    public function jsonSerialize(): array|string|null
    {
        if ($this->component instanceof ComponentInterface) {
            $serialization = $this->component->jsonSerialize();
            $serialization['__class'] = get_class($this->component);

            return $serialization;
        }

        return $this->component;
    }
}
