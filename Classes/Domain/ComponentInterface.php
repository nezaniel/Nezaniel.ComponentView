<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Domain;

/**
 * The interface for self-rendering components
 */
interface ComponentInterface extends \Stringable, \JsonSerializable
{
    public function render(): string;

    /**
     * Serializes the component to cache
     *
     * @return array<string,string|ComponentInterface|null>
     */
    public function jsonSerialize(): array;
}
