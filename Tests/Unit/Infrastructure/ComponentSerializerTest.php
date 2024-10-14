<?php

/*
 * This file is part of the Nezaniel.ComponentView package
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Tests\Unit\Infrastructure;

use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Nezaniel\ComponentView\Domain\CacheDirective;
use Nezaniel\ComponentView\Domain\CacheSegment;
use Nezaniel\ComponentView\Domain\ComponentCollection;
use Nezaniel\ComponentView\Domain\ComponentInterface;
use Nezaniel\ComponentView\Domain\NodeMetadataWrapper;
use Nezaniel\ComponentView\Domain\RenderingEntryPoint;
use Nezaniel\ComponentView\Infrastructure\ComponentSerializer;
use Nezaniel\ComponentView\Tests\Unit\Fixtures\MyComponent;
use Nezaniel\ComponentView\Tests\Unit\Fixtures\MyEnum;
use Nezaniel\ComponentView\Tests\Unit\Fixtures\MyProplessSubComponent;
use Nezaniel\ComponentView\Tests\Unit\Fixtures\MySubComponent;
use Nezaniel\ComponentView\Tests\Unit\Fixtures\MySubComponents;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the component serializer
 */
final class ComponentSerializerTest extends TestCase
{
    private ComponentSerializer $subject;

    /**
     * @param array<mixed> $data
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->subject = new ComponentSerializer();
    }

    /**
     * @dataProvider componentProvider
     * @param array<string,mixed> $expectedSerialization
     */
    public function testSerializeComponent(ComponentInterface $component, array $expectedSerialization): void
    {
        Assert::assertSame($expectedSerialization, $this->subject->serializeComponent($component));
    }

    /**
     * @return iterable<string,mixed>
     */
    public static function componentProvider(): iterable
    {
        yield 'MyProplessSubComponent' => [
            new MyProplessSubComponent(),
            [
                '__class' => MyProplessSubComponent::class
            ]
        ];

        yield 'MySubComponent' => [
            new MySubComponent('my text'),
            [
                '__class' => MySubComponent::class,
                'content' => [
                    '__type' => 'string',
                    'value' => 'my text'
                ]
            ]
        ];

        yield 'MySubComponents' => [
            new MySubComponents(
                new MySubComponent('my text'),
                new MySubComponent('my other text')
            ),
            [
                '__class' => MySubComponents::class,
                'subComponents' => [
                    [
                        '__class' => MySubComponent::class,
                        'content' => [
                            '__type' => 'string',
                            'value' => 'my text'
                        ]
                    ],
                    [
                        '__class' => MySubComponent::class,
                        'content' => [
                            '__type' => 'string',
                            'value' => 'my other text'
                        ]
                    ]
                ]
            ]
        ];

        yield 'CacheSegment' => [
            new CacheSegment(
                new CacheDirective(
                    'my-identifier',
                    NodeAggregateId::fromString('nody-mc-nodeface'),
                    null,
                    new RenderingEntryPoint('MyClass', 'myMethod')
                ),
                new MySubComponent('my text')
            ),
            [
                '__class' => CacheSegment::class,
                'cacheDirective' => [
                    'cacheEntryId' => 'my-identifier',
                    'nodeAggregateId' => 'nody-mc-nodeface',
                    'nodeName' => null,
                    'entryPoint' => 'MyClass::myMethod'
                ]
            ]
        ];

        yield 'NodeMetadataWrapper' => [
            new NodeMetadataWrapper(
                [
                    'data-wat' => 'whatever'
                ],
                new MySubComponent('my text'),
            ),
            [
                '__class' => NodeMetadataWrapper::class,
                'attributes' => [
                    '__type' => 'array',
                    'value' => [
                        'data-wat' => 'whatever'
                    ]
                ],
                'content' => [
                    '__class' => MySubComponent::class,
                    'content' => [
                        '__type' => 'string',
                        'value' => 'my text'
                    ]
                ],
                'script' => null
            ]
        ];

        yield 'MyComponent' => [
            new MyComponent(
                'plain text',
                42,
                47.11,
                true,
                new Uri('https://neos.io'),
                MyEnum::VALUE_DEFAULT,
                new MySubComponent('my text'),
                new MySubComponents(
                    new MySubComponent('my text'),
                    new MySubComponent('my other text')
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
            [
                '__class' => MyComponent::class,
                'string' => [
                    '__type' => 'string',
                    'value' => 'plain text'
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
                    'value' => MyEnum::VALUE_DEFAULT->value
                ],
                'content' => [
                    '__class' => MySubComponent::class,
                    'content' => [
                        '__type' => 'string',
                        'value' => 'my text'
                    ]
                ],
                'mySubComponents' => [
                    '__class' => MySubComponents::class,
                    'subComponents' => [
                        [
                            '__class' => MySubComponent::class,
                            'content' => [
                                '__type' => 'string',
                                'value' => 'my text'
                            ]
                        ],
                        [
                            '__class' => MySubComponent::class,
                            'content' => [
                                '__type' => 'string',
                                'value' => 'my other text'
                            ]
                        ]
                    ]
                ],
                'myProplessSubComponent' => [
                    '__class' => MyProplessSubComponent::class
                ],
                'whatever' => [
                    '__class' => MySubComponent::class,
                    'content' =>  [
                        '__type' => 'string',
                        'value' => 'random text'
                    ]
                ],
                'whateverOrNothing' => null,
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
            ]
        ];

        yield 'ComponentCollection' => [
            new ComponentCollection(
                'plain text',
                new MyProplessSubComponent(),
                new MySubComponent('my text'),
                new MySubComponents(
                    new MySubComponent('my text'),
                    new MySubComponent('my other text')
                ),
                new CacheSegment(
                    new CacheDirective(
                        'my-identifier',
                        NodeAggregateId::fromString('nody-mc-nodeface'),
                        null,
                        new RenderingEntryPoint('MyClass', 'myMethod')
                    ),
                    new MySubComponent('my text')
                ),
                new NodeMetadataWrapper(
                    [
                        'data-wat' => 'whatever'
                    ],
                    new MySubComponent('my text'),
                )
            ),
            [
                '__class' => ComponentCollection::class,
                'components' => [
                    'plain text',
                    [
                        '__class' => MyProplessSubComponent::class
                    ],
                    [
                        '__class' => MySubComponent::class,
                        'content' => [
                            '__type' => 'string',
                            'value' => 'my text'
                        ]
                    ],
                    [
                        '__class' => MySubComponents::class,
                        'subComponents' => [
                            [
                                '__class' => MySubComponent::class,
                                'content' => [
                                    '__type' => 'string',
                                    'value' => 'my text'
                                ]
                            ],
                            [
                                '__class' => MySubComponent::class,
                                'content' => [
                                    '__type' => 'string',
                                    'value' => 'my other text'
                                ]
                            ]
                        ]
                    ],
                    [
                        '__class' => CacheSegment::class,
                        'cacheDirective' => [
                            'cacheEntryId' => 'my-identifier',
                            'nodeAggregateId' => 'nody-mc-nodeface',
                            'nodeName' => null,
                            'entryPoint' => 'MyClass::myMethod'
                        ]
                    ],
                    [
                        '__class' => NodeMetadataWrapper::class,
                        'attributes' => [
                            '__type' => 'array',
                            'value' => [
                                'data-wat' => 'whatever'
                            ]
                        ],
                        'content' => [
                            '__class' => MySubComponent::class,
                            'content' => [
                                '__type' => 'string',
                                'value' => 'my text'
                            ]
                        ],
                        'script' => null
                    ]
                ]
            ]
        ];
    }
}
