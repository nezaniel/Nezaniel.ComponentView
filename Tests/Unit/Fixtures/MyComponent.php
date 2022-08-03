<?php

/*
 * This file is part of the Nezaniel.ComponentView package
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Tests\Unit\Fixtures;

use Nezaniel\ComponentView\Domain\ComponentCollection;
use Nezaniel\ComponentView\Domain\ComponentInterface;
use Nezaniel\ComponentView\Domain\QualifiedComponentSerialization;
use Psr\Http\Message\UriInterface;

final class MyComponent implements ComponentInterface
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

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'string' => $this->string,
            'int' => $this->int,
            'float' => $this->float,
            'bool' => $this->bool,
            'uri' => (string)$this->uri,
            'enum' => $this->enum->value,
            'content' => $this->content,
            'mySubComponents' => $this->mySubComponents,
            'myProplessSubComponent' => $this->myProplessSubComponent,
            'whatever' => QualifiedComponentSerialization::create($this->whatever),
            'whateverOrNothing' => QualifiedComponentSerialization::create($this->whateverOrNothing),
            'whateverOrString' => QualifiedComponentSerialization::create($this->whateverOrString),
            'anotherWhateverOrString' => QualifiedComponentSerialization::create($this->anotherWhateverOrString),
            'surpriseCollection' => QualifiedComponentSerialization::create($this->surpriseCollection),
            'plannedCollection' => $this->plannedCollection
        ];
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
