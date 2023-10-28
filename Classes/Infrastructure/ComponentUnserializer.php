<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Infrastructure;

use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\Flow\Annotations as Flow;
use Nezaniel\ComponentView\Application\CacheTagSet;
use Nezaniel\ComponentView\Application\ComponentCache;
use Nezaniel\ComponentView\Application\ComponentViewRuntimeVariables;
use Nezaniel\ComponentView\Domain\CacheDirective;
use Nezaniel\ComponentView\Domain\CacheSegment;
use Nezaniel\ComponentView\Domain\ComponentCollection;
use Nezaniel\ComponentView\Domain\ComponentInterface;
use Nezaniel\ComponentView\Application\ContentRenderer;
use Nezaniel\ComponentView\Domain\RenderingEntryPoint;
use Psr\Http\Message\UriInterface;

/**
 * The component unserializer infrastructure service
 */
#[Flow\Scope('singleton')]
class ComponentUnserializer
{
    #[Flow\Inject]
    protected ContentRenderer $contentRenderer;

    /** @phpstan-ignore-next-line We can't declare recursive array types */
    public function unserializeComponent(
        array $data,
        ComponentViewRuntimeVariables $runtimeVariables,
        ComponentCache $cache
    ): ?ComponentInterface {
        $className = $data['__class'];
        if (!is_string($className)) {
            throw new \InvalidArgumentException('Class identifiers must be strings', 1659564301);
        }
        /** @var class-string $className */
        if (!in_array(ComponentInterface::class, class_implements($className) ?: [])) {
            throw new \InvalidArgumentException(
                'Can only unserialize objects of type ' . ComponentInterface::class . ', ' . $className . ' given',
                1659563886
            );
        }
        if ($className === ComponentCollection::class) {
            return $this->unserializeComponentCollection(
                $data,
                $runtimeVariables,
                $cache
            );
        }
        if ($className === CacheSegment::class) {
            return $this->resolveCacheDirective(
                $this->unserializeCacheDirective($data['cacheDirective']),
                $runtimeVariables,
                $cache
            );
        }
        $properties = [];
        $reflectionClass = new \ReflectionClass($className);
        if (IsCollectionType::isSatisfiedByReflectionClass($reflectionClass)) {
            return $this->unserializeCollectionType(
                $reflectionClass,
                $data,
                $runtimeVariables,
                $cache
            );
        } else {
            foreach ($reflectionClass->getProperties() as $reflectionProperty) {
                $propertyValue = $data[$reflectionProperty->name];

                if (is_null($propertyValue)) {
                    $properties[$reflectionProperty->name] = $propertyValue;
                    continue;
                }

                $propertyType = $propertyValue['__class']
                    ?? $propertyValue['__type'];

                /** @var class-string|string|null $propertyType */
                $properties[$reflectionProperty->name] = match ($propertyType) {
                    null => throw new \InvalidArgumentException(
                        'Cannot unserialize untyped property ' . $reflectionProperty->name,
                        1659214435
                    ),
                    'string', 'int', 'bool', 'float', 'array' => $propertyValue['value'],
                    UriInterface::class, Uri::class => new Uri($propertyValue['value']),
                    default => \enum_exists($propertyType)
                        ? $propertyType::from($propertyValue['value'])
                        : $this->unserializeComponent(
                            $propertyValue,
                            $runtimeVariables,
                            $cache
                        )
                };
            }
        }

        /** @var ComponentInterface $collection */
        $collection = new $className(...$properties);

        return $collection;
    }

    /** @phpstan-ignore-next-line We can't declare recursive array types */
    public function unserializeComponentCollection(
        array $serialization,
        ComponentViewRuntimeVariables $runtimeVariables,
        ComponentCache $cache
    ): ComponentCollection {
        $components = [];
        foreach ($serialization['components'] as $serializedComponent) {
            if (is_string($serializedComponent)) {
                $components[] = $serializedComponent;
            } else {
                $component = $this->unserializeComponent(
                    $serializedComponent,
                    $runtimeVariables,
                    $cache
                );
                if ($component instanceof ComponentInterface) {
                    $components[] = $component;
                }
            }
        }

        return new ComponentCollection(...$components);
    }

    /** @phpstan-ignore-next-line We can't declare recursive array types */
    public function unserializeCollectionType(
        \ReflectionClass $reflectionClass,
        array $data,
        ComponentViewRuntimeVariables $runtimeVariables,
        ComponentCache $cache
    ): ComponentInterface {
        $className = $reflectionClass->name;
        if (!in_array(ComponentInterface::class, class_implements($className) ?: [])) {
            throw new \InvalidArgumentException(
                'Can only unserialize objects of type ' . ComponentInterface::class
                    . ', ' . $className . ' given',
                1659563886
            );
        }
        $components = [];
        $collectionPropertyName = $reflectionClass->getProperties()[0]->name;
        foreach ($data[$collectionPropertyName] as $serializedComponent) {
            $component = $this->unserializeComponent(
                $serializedComponent,
                $runtimeVariables,
                $cache
            );
            if ($component instanceof ComponentInterface) {
                $components[] = $component;
            }
        }

        /** @var ComponentInterface $component */
        $component = new $className(...$components);

        return $component;
    }

    /**
     * @param array<string,string|null> $data
     */
    public function unserializeCacheDirective(array $data): CacheDirective
    {
        if (is_null($data['cacheEntryId'])) {
            throw new \InvalidArgumentException(
                'Cannot unserialize cache directives without a cache entry identifier',
                1659563561
            );
        }
        if (is_null($data['nodeAggregateId'])) {
            throw new \InvalidArgumentException(
                'Cannot unserialize cache directives without a node aggregate identifier',
                1659563582
            );
        }
        return new CacheDirective(
            $data['cacheEntryId'],
            NodeAggregateId::fromString($data['nodeAggregateId']),
            $data['nodeName'] ? NodeName::fromString($data['nodeName']) : null,
            $data['entryPoint'] ? RenderingEntryPoint::fromString($data['entryPoint']) : null
        );
    }

    private function resolveCacheDirective(
        CacheDirective $cacheDirective,
        ComponentViewRuntimeVariables $runtimeVariables,
        ComponentCache $cache
    ): ?ComponentInterface {
        $cachedComponent = $cache->findComponent($cacheDirective->cacheEntryId, $runtimeVariables);
        if ($cachedComponent instanceof ComponentInterface) {
            return $cachedComponent;
        } else {
            $node = $runtimeVariables->subgraph->findNodeById($cacheDirective->nodeAggregateId);
            if (!$node instanceof Node) {
                return null;
            }
            if ($cacheDirective->entryPoint && $cacheDirective->entryPoint->canResolve()) {
                return (new $cacheDirective->entryPoint->className())->$cacheDirective->entryPoint->methodName($runtimeVariables);
            }
            if ($cacheDirective->nodeName instanceof NodeName) {
                return $this->contentRenderer->forContentCollectionChildNode(
                    $node,
                    $cacheDirective->nodeName,
                    $runtimeVariables
                );
            } elseif ($node->nodeType?->isOfType('Neos.Neos:ContentCollection')) {
                return $this->contentRenderer->forContentCollection($node, $runtimeVariables);
            }
            $cacheTags = new CacheTagSet();

            return $this->contentRenderer->delegate(
                $node,
                $runtimeVariables,
                $cacheTags
            );
        }
    }
}
