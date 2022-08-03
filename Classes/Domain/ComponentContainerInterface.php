<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Domain;

/**
 * The interface for component containers
 */
interface ComponentContainerInterface
{
    public function isEmpty(): bool;
}
