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
use PHPUnit\Framework\TestCase;

/**
 * Test for the UriService
 */
final class ComponentUnserializerTest extends TestCase
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
                        'Data/Temporary/Cache'
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
                    'string' => [
                        '__type' => 'string',
                        'value' => 'myString'
                    ],
                    'int' => [
                        '__type' => 'int',
                        'value' => 42
                    ],
                    'float' => [
                        '__type' => 'float',
                        'value' => 47.11
                    ],
                    'bool' => [
                        '__type' => 'bool',
                        'value' => true
                    ],
                    'uri' => [
                        '__class' => Uri::class,
                        'value' => 'https://neos.io'
                    ],
                    'enum' => [
                        '__class' => MyEnum::class,
                        'value' => 'default'
                    ],
                    'content' => [
                        '__class' => MySubComponent::class,
                        'content' => [
                            '__type' => 'string',
                            'value' => 'text'
                        ]
                    ],
                    'mySubComponents' => [
                        '__class' => MySubComponents::class,
                        'subComponents' => [
                            [
                                '__class' => MySubComponent::class,
                                'content' => [
                                    '__type' => 'string',
                                    'value' => 'text1'
                                ]
                            ],
                            [
                                '__class' => MySubComponent::class,
                                'content' => [
                                    '__type' => 'string',
                                    'value' => 'text2'
                                ]
                            ]
                        ]
                    ],
                    'whatever' => [
                        '__class' => MySubComponent::class,
                        'content' => [
                            '__type' => 'string',
                            'value' => 'random text'
                        ]
                    ],
                    'whateverOrNothing' => null,
                    'myProplessSubComponent' => [
                        '__class' => MyProplessSubComponent::class,
                    ],
                    'whateverOrString' => [
                        '__type' => 'string',
                        'value' => 'whatever'
                    ],
                    'anotherWhateverOrString' => [
                        '__class' => MySubComponent::class,
                        'content' => [
                            '__type' => 'string',
                            'value' => 'whatever'
                        ]
                    ],
                    'surpriseCollection' => [
                        '__class' => ComponentCollection::class,
                        'components' => [
                            [
                                '__class' => MySubComponent::class,
                                'content' => [
                                    '__type' => 'string',
                                    'value' => 'surprise text 1'
                                ]
                            ],
                            [
                                '__class' => MySubComponent::class,
                                'content' => [
                                    '__type' => 'string',
                                    'value' => 'surprise text 2'
                                ]
                            ]
                        ]
                    ],
                    'plannedCollection' => [
                        '__class' => ComponentCollection::class,
                        'components' => [
                            [
                                '__class' => MySubComponent::class,
                                'content' => [
                                    '__type' => 'string',
                                    'value' => 'planned text 1'
                                ]
                            ],
                            [
                                '__class' => MySubComponent::class,
                                'content' => [
                                    '__type' => 'string',
                                    'value' => 'planned text 2'
                                ]
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
