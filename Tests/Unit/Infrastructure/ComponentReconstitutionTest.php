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
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\DefaultNodeLabelGeneratorFactory;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphIdentity;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Nezaniel\ComponentView\Application\ComponentCache;
use Nezaniel\ComponentView\Infrastructure\ComponentSerializer;
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
 * Test for ComponentReconstitution
 */
final class ComponentReconstitutionTest extends TestCase
{
    private ?ComponentUnserializer $unserializer = null;

    private ?ContentSubgraphInterface $subgraph = null;

    private ?Node $dummyNode = null;

    private ?ComponentCache $cache = null;

    public function setUp(): void
    {
        parent::setUp();
        $this->unserializer = new ComponentUnserializer();
        $this->subgraph = new TestingSubgraph();
        $this->dummyNode = new Node(
            ContentSubgraphIdentity::create(
                ContentRepositoryId::fromString('default'),
                ContentStreamId::fromString('cs-id'),
                DimensionSpacePoint::fromArray([]),
                VisibilityConstraints::withoutRestrictions()
            ),
            NodeAggregateId::fromString('nody-mc-nodeface'),
            OriginDimensionSpacePoint::fromArray([]),
            NodeAggregateClassification::CLASSIFICATION_REGULAR,
            NodeTypeName::fromString('Nezaniel.ComponentView.Testing:Node'),
            new NodeType(
                NodeTypeName::fromString('Nezaniel.ComponentView.Testing:Node'),
                [],
                [],
                new NodeTypeManager(
                    fn (): array => [],
                    new DefaultNodeLabelGeneratorFactory(),
                    null
                ),
                new DefaultNodeLabelGeneratorFactory()
            ),
            new PropertyCollection(
                SerializedPropertyValues::fromArray([]),
                new PropertyConverter(new Serializer())
            ),
            null,
            Timestamps::create(
                new \DateTimeImmutable(),
                new \DateTimeImmutable(),
                new \DateTimeImmutable(),
                new \DateTimeImmutable(),
            )
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

    public function testReconstituteComponent()
    {
        $component = new MyComponent(
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
        );
        Assert::assertEquals(
            $component,
            $this->unserializer->unserializeComponent(
                ComponentSerializer::serializeComponent($component),
                $this->subgraph,
                $this->dummyNode,
                $this->dummyNode,
                false,
                $this->cache
            )
        );
    }
}
