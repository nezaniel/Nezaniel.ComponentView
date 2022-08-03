<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Infrastructure;

use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\ContentContext;
use Nezaniel\ComponentView\Application\CacheTags;
use Nezaniel\ComponentView\Application\ComponentCache;
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

    public function unserializeComponent(
        array $data,
        ContentContext $subgraph,
        bool $inBackend,
        ComponentCache $cache
    ): ComponentInterface {
        $className = $data['__class'];
        if ($className === ComponentCollection::class) {
            return $this->unserializeComponentCollection($data, $subgraph, $inBackend, $cache);
        }
        if ($className === CacheSegment::class) {
            return $this->resolveCacheDirective(
                $this->unserializeCacheDirective($data['cacheDirective']),
                $subgraph,
                $inBackend,
                $cache
            );
        }
        $properties = [];
        $reflectionClass = new \ReflectionClass($className);
        if (IsCollectionType::isSatisfiedByReflectionClass($reflectionClass)) {
            return $this->unserializeCollectionType($reflectionClass, $data, $subgraph, $inBackend, $cache);
        } else {
            foreach ($reflectionClass->getProperties() as $reflectionProperty) {
                $propertyValue = $data[$reflectionProperty->name];

                if (is_null($propertyValue)) {
                    $properties[$reflectionProperty->name] = $propertyValue;
                    continue;
                }

                if (is_string($propertyValue)) {
                    \Neos\Flow\var_dump($data);
                    exit();
                }

                $propertyType = $propertyValue['__class']
                    ?? $propertyValue['__type'];

                /** @var class-string|string|null $propertyType */
                $properties[$reflectionProperty->name] = match($propertyType) {
                    null => throw new \InvalidArgumentException(
                        'Cannot unserialize untyped property ' . $reflectionProperty->name,
                        1659214435
                    ),
                    'string', 'int', 'bool', 'float', 'array' => $propertyValue,
                    UriInterface::class, Uri::class => new Uri($propertyValue['value']),
                    default => \enum_exists($propertyType)
                        ? $propertyType::from($propertyValue)
                        : $this->unserializeComponent($propertyValue, $subgraph, $inBackend, $cache)
                };
            }
        }

        return new $className(...$properties);
    }

    public function unserializeComponentCollection(array $serialization, ContentContext $subgraph, bool $inBackend, ComponentCache $cache): ComponentCollection
    {
        $components = [];
        foreach ($serialization['components'] as $serializedComponent) {
            if (is_string($serializedComponent)) {
                $components[] = $serializedComponent;
            } else {
                $components[] = $this->unserializeComponent(
                    $serializedComponent,
                    $subgraph,
                    $inBackend,
                    $cache
                );
            }
        }

        return new ComponentCollection(...$components);
    }

    public function unserializeCollectionType(
        \ReflectionClass $reflectionClass,
        array $data,
        ContentContext $subgraph,
        bool $inBackend,
        ComponentCache $cache
    ): ComponentInterface {
        $components = [];
        $collectionPropertyName = $reflectionClass->getProperties()[0]->name;
        foreach ($data[$collectionPropertyName] as $serializedComponent) {
            $components[] = $this->unserializeComponent(
                $serializedComponent,
                $subgraph,
                $inBackend,
                $cache
            );
        }

        return new $reflectionClass->name(...$components);
    }

    private function resolvePropertyType(\ReflectionType $reflectionType, mixed $propertyValue): ?string
    {
        if ($reflectionType instanceof \ReflectionUnionType) {
            $bestMatchedType = null;
            foreach ($reflectionType->getTypes() as $unionType) {
                if (
                    $unionType->getName() === 'string' && is_string($propertyValue)
                    || $unionType->getName() === 'int' && is_int($propertyValue)
                    || $unionType->getName() === 'float' && is_float($propertyValue)
                    || $unionType->getName() === 'bool' && is_bool($propertyValue)
                    || $unionType->getName() === 'array' && is_array($propertyValue)
                ) {
                    if (is_null($bestMatchedType)) $bestMatchedType = $unionType->getName();
                } elseif (
                    ($unionType->getName() === UriInterface::class
                        || $unionType->getName() === Uri::class)
                    && is_string($propertyValue)
                ) {
                    $bestMatchedType = UriInterface::class;
                }
            }
            return $bestMatchedType;
        } elseif ($reflectionType instanceof \ReflectionNamedType) {
            return $reflectionType->getName();
        } else {
            return null;
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    public function unserializeCacheDirective(array $data): CacheDirective
    {
        return new CacheDirective(
            $data['cacheEntryIdentifier'],
            NodeAggregateIdentifier::fromString($data['nodeAggregateIdentifier']),
            $data['nodeName'] ? NodeName::fromString($data['nodeName']) : null,
            $data['entryPoint'] ? RenderingEntryPoint::fromString($data['entryPoint']) : null
        );
    }

    private function resolveCacheDirective(CacheDirective $cacheDirective, ContentContext $subgraph, bool $inBackend, ComponentCache $cache): ?ComponentInterface
    {
        $cachedComponent = $cache->findComponent($cacheDirective->cacheEntryIdentifier, $subgraph, $inBackend);
        if ($cachedComponent instanceof ComponentInterface) {
            return $cachedComponent;
        } else {
            $node = $subgraph->getNodeByIdentifier((string)$cacheDirective->nodeAggregateIdentifier);
            if (!$node instanceof Node) {
                return null;
            }
            if ($cacheDirective->entryPoint && $cacheDirective->entryPoint->canResolve()) {
                return (new $cacheDirective->entryPoint->className)->$cacheDirective->entryPoint->methodName($node, $subgraph, $inBackend);
            }
            if ($cacheDirective->nodeName instanceof NodeName) {
                return $this->contentRenderer->forContentCollectionChildNode(
                    $node,
                    $cacheDirective->nodeName,
                    $subgraph,
                    $inBackend
                );
            } elseif ($node->getNodeType()->isOfType('Neos.Neos:ContentCollection')) {
                return $this->contentRenderer->forContentCollection($node, $subgraph, $inBackend);
            }
            return $this->contentRenderer->delegate(
                $node,
                $subgraph,
                $inBackend,
                new CacheTags()
            );
        }
    }
}
