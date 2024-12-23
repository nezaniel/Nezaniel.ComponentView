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
        return '<dl>
            <dt>string</dt>
            <dd>' . $this->string . '</dd>
            <dt>int</dt>
            <dd>' . $this->int . '</dd>
            <dt>float</dt>
            <dd>' . $this->float . '</dd>
            <dt>bool</dt>
            <dd>' . $this->bool . '</dd>
            <dt>uri</dt>
            <dd>' . $this->uri . '</dd>
            <dt>enum</dt>
            <dd>' . $this->enum->value . '</dd>
            <dt>content</dt>
            <dd>' . $this->content . '</dd>
            <dt>mySubComponents</dt>
            <dd>' . $this->mySubComponents . '</dd>
            <dt>myProplessSubComponent</dt>
            <dd>' . $this->myProplessSubComponent . '</dd>
            <dt>whatever</dt>
            <dd>' . $this->whatever . '</dd>
            <dt>whateverOrNothing</dt>
            <dd>' . $this->whateverOrNothing . '</dd>
            <dt>whateverOrString</dt>
            <dd>' . $this->whateverOrString . '</dd>
            <dt>anotherWhateverOrString</dt>
            <dd>' . $this->anotherWhateverOrString . '</dd>
            <dt>surpriseCollection</dt>
            <dd>' . $this->surpriseCollection . '</dd>
            <dt>plannedCollection</dt>
            <dd>' . $this->plannedCollection . '</dd>
        </dl>';
    }
}
