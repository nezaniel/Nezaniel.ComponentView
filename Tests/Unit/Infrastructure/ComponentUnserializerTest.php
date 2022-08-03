<?php

/*
 * This file is part of the Nezaniel.ComponentView package
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Tests\Unit\Infrastructure;

use GuzzleHttp\Psr7\Uri;
use Neos\Cache\Backend\FileBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Domain\Service\ContentContext;
use Nezaniel\ComponentView\Application\ComponentCache;
use Nezaniel\ComponentView\Infrastructure\ComponentUnserializer;
use Nezaniel\ComponentView\Tests\Unit\Fixtures\MyComponent;
use Nezaniel\ComponentView\Tests\Unit\Fixtures\MyEnum;
use Nezaniel\ComponentView\Tests\Unit\Fixtures\MyProplessSubComponent;
use Nezaniel\ComponentView\Tests\Unit\Fixtures\MySubComponent;
use Nezaniel\ComponentView\Tests\Unit\Fixtures\MySubComponents;
use Nezaniel\ComponentView\Domain\ComponentCollection;
use PHPUnit\Framework\Assert;

/**
 * Test for the UriService
 */
final class ComponentUnserializerTest extends UnitTestCase
{
    private ?ComponentUnserializer $subject = null;

    private ?ContentContext $subgraph = null;

    private ?ComponentCache $cache = null;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = new ComponentUnserializer();
        $this->subgraph = new ContentContext(
            'live',
            new \DateTimeImmutable(),
            [],
            [],
            false,
            false,
            false
        );
        $this->cache = new ComponentCache(
            new StringFrontend(
                'wat',
                new FileBackend(
                    new EnvironmentConfiguration(
                        'wat',
                        FLOW_PATH_DATA . 'Temporary/Cache'
                    )
                )
            )
        );
    }

    public function testUnserializeComponent()
    {
        Assert::assertEquals(
            new MyComponent(
                'myString',
                42,
                47.11,
                true,
                new Uri('https://neos.io'),
                MyEnum::VALUE_DEFAULT,
                new MySubComponent('text'),
                new MySubComponents(
                    new MySubComponent('text1'),
                    new MySubComponent('text2'),
                ),
                new MyProplessSubComponent(),
                new MySubComponent('random text'),
                null,
                'whatever',
                new MySubComponent('whatever'),
                new ComponentCollection(
                    new MySubComponent('surprise text 1'),
                    new MySubComponent('surprise text 2')
                ),
                new ComponentCollection(
                    new MySubComponent('planned text 1'),
                    new MySubComponent('planned text 2')
                )
            ),
            $this->subject->unserializeComponent(
                [
                    '__class' => MyComponent::class,
                    'string' => 'myString',
                    'int' => 42,
                    'float' => 47.11,
                    'bool' => true,
                    'uri' => 'https://neos.io',
                    'enum' => 'default',
                    'content' => [
                        '__class' => MySubComponent::class,
                        'content' => 'text'
                    ],
                    'mySubComponents' => [
                        '__class' => MySubComponents::class,
                        'subComponents' => [
                            [
                                '__class' => MySubComponent::class,
                                'content' => 'text1'
                            ],
                            [
                                '__class' => MySubComponent::class,
                                'content' => 'text2'
                            ]
                        ]
                    ],
                    'whatever' => [
                        '__class' => MySubComponent::class,
                        'content' => 'random text'
                    ],
                    'whateverOrNothing' => null,
                    'myProplessSubComponent' => [
                        '__class' => MyProplessSubComponent::class,
                    ],
                    'whateverOrString' => 'whatever',
                    'anotherWhateverOrString' => [
                        '__class' => MySubComponent::class,
                        'content' => 'whatever'
                    ],
                    'surpriseCollection' => [
                        '__class' => ComponentCollection::class,
                        'components' => [
                            [
                                '__class' => MySubComponent::class,
                                'content' => 'surprise text 1'
                            ],
                            [
                                '__class' => MySubComponent::class,
                                'content' => 'surprise text 2'
                            ]
                        ]
                    ],
                    'plannedCollection' => [
                        '__class' => ComponentCollection::class,
                        'components' => [
                            [
                                '__class' => MySubComponent::class,
                                'content' => 'planned text 1'
                            ],
                            [
                                '__class' => MySubComponent::class,
                                'content' => 'planned text 2'
                            ]
                        ]
                    ]
                ],
                $this->subgraph,
                false,
                $this->cache
            )
        );
    }
}
