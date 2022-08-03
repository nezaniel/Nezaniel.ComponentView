<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Domain;

/**
 * The interface for self-rendering components
 */
interface ComponentInterface extends \Stringable
{
    public function render(): string;
}
