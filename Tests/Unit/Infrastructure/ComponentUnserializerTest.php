<?php

/*
 * This file is part of the Nezaniel.ComponentView package
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Tests\Unit\Infrastructure;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Neos\Cache\Backend\FileBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Frontend\StringFrontend;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Neos\Domain\Model\RenderingMode;
use Nezaniel\ComponentView\Application\ComponentCache;
use Nezaniel\ComponentView\Application\ComponentViewRuntimeVariables;
use Nezaniel\ComponentView\Infrastructure\ComponentUnserializer;
use Nezaniel\ComponentView\Tests\Unit\Fixtures\MyComponent;
use Nezaniel\ComponentView\Tests\Unit\Fixtures\MyEnum;
use Nezaniel\ComponentView\Tests\Unit\Fixtures\MyProplessSubComponent;
use Nezaniel\ComponentView\Tests\Unit\Fixtures\MySubComponent;
use Nezaniel\ComponentView\Tests\Unit\Fixtures\MySubComponents;
use Nezaniel\ComponentView\Domain\ComponentCollection;
use Nezaniel\ComponentView\Tests\Unit\Fixtures\TestingSubgraph;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Serializer;

/**
 * Test for the ComponentUnserializer
 */
final class ComponentUnserializerTest extends TestCase
{
    private ComponentUnserializer $subject;

    private ContentSubgraphInterface $subgraph;

    private Node $dummyNode;

    private ComponentCache $cache;

    /**
     * @param array<mixed> $data
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->subject = new ComponentUnserializer();
        $this->subgraph = new TestingSubgraph();
        $this->dummyNode = Node::create(
            ContentRepositoryId::fromString('default'),
            WorkspaceName::forLive(),
            DimensionSpacePoint::createWithoutDimensions(),
            NodeAggregateId::fromString('nody-mc-nodeface'),
            OriginDimensionSpacePoint::fromArray([]),
            NodeAggregateClassification::CLASSIFICATION_REGULAR,
            NodeTypeName::fromString('Nezaniel.ComponentView.Testing:Node'),
            new PropertyCollection(
                SerializedPropertyValues::fromArray([]),
                new PropertyConverter(new Serializer())
            ),
            null,
            NodeTags::createEmpty(),
            Timestamps::create(
                new \DateTimeImmutable(),
                new \DateTimeImmutable(),
                new \DateTimeImmutable(),
                new \DateTimeImmutable(),
            ),
            VisibilityConstraints::withoutRestrictions(),
            null,
            ContentStreamId::fromString('cs-id')
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

    public function testUnserializeComponent(): void
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
                new ComponentViewRuntimeVariables(
                    $this->dummyNode,
                    $this->dummyNode,
                    $this->subgraph,
                    ActionRequest::fromHttpRequest(ServerRequest::fromGlobals()),
                    RenderingMode::createFrontend(),
                ),
                $this->cache
            )
        );
    }
}
