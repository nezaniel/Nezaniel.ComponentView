<?php

/*
 * This file is part of the Nezaniel.ComponentView package
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Tests\Unit\Domain;

use Nezaniel\ComponentView\Domain\RenderingEntryPoint;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class RenderingEntryPointTest extends TestCase
{
    /**
     * @dataProvider methodProvider
     */
    public function testFromMethod(string $method, RenderingEntryPoint $expectedEntryPoint): void
    {
        Assert::assertEquals($expectedEntryPoint, RenderingEntryPoint::fromMethod($method));
    }

    /**
     * @return array<int,array<int,mixed>>
     */
    public static function methodProvider(): array
    {
        return [
            [
                'Acme\Site\Presentation\DummyFactory::createComponent',
                new RenderingEntryPoint('Acme\Site\Presentation\DummyFactory', 'createComponent')
            ],
            [
                'Acme\Site\Presentation\DummyFactory_Original::createComponent',
                new RenderingEntryPoint('Acme\Site\Presentation\DummyFactory', 'createComponent')
            ]
        ];
    }
}
