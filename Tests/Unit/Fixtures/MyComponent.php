<?php

/*
 * This file is part of the Nezaniel.ComponentView package
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Tests\Unit\Fixtures;

use Nezaniel\ComponentView\Domain\AbstractComponent;
use Nezaniel\ComponentView\Domain\ComponentCollection;
use Nezaniel\ComponentView\Domain\ComponentInterface;
use Psr\Http\Message\UriInterface;

final readonly class MyComponent extends AbstractComponent
{
    public function __construct(
        private string $string,
        private int $int,
        private float $float,
        private bool $bool,
        private UriInterface $uri,
        private MyEnum $enum,
        private MySubComponent $content,
        private MySubComponents $mySubComponents,
        private MyProplessSubComponent $myProplessSubComponent,
        private ComponentInterface $whatever,
        private ?ComponentInterface $whateverOrNothing,
        private ComponentInterface|string $whateverOrString,
        private ComponentInterface|string $anotherWhateverOrString,
        private ComponentInterface $surpriseCollection,
        private ComponentCollection $plannedCollection
    ){
    }

    public function render(): string
    {
        return '';
    }
}
