<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Domain;

/**
 * An abstract component, enforcing string casting by calling render()
 */
abstract class AbstractComponent implements ComponentInterface
{
    final public function __toString(): string
    {
        return $this->render();
    }
}
