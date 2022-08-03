<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Infrastructure;

use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Annotations as Flow;
use Nezaniel\ComponentView\Domain\CacheSegment;
use Nezaniel\ComponentView\Domain\ComponentCollection;
use Nezaniel\ComponentView\Domain\ComponentInterface;
use Psr\Http\Message\UriInterface;

/**
 * The component serializer infrastructure service
 */
#[Flow\Scope('singleton')]
class ComponentSerializer
{
    /**
     * @return array<string,mixed>
     */
    public static function serializeComponent(ComponentInterface $component): array
    {
        $reflectionClass = new \ReflectionClass($component);
        if ($component instanceof ComponentCollection) {
            return self::serializeComponentCollection($component, $reflectionClass);
        } elseif ($component instanceof CacheSegment) {
            return $component->serializeForCache();
        } elseif (IsCollectionType::isSatisfiedByReflectionClass($reflectionClass)) {
            return self::serializeCollectionType($component, $reflectionClass);
        } else {
            $result = [
                '__class' => get_class($component)
            ];
            foreach ($reflectionClass->getProperties() as $reflectionProperty) {
                $propertyValue = $reflectionProperty->getValue($component);
                if (is_null($propertyValue)) {
                    $serializedPropertyValue = null;
                } elseif ($propertyValue instanceof ComponentInterface) {
                    $serializedPropertyValue = self::serializeComponent($propertyValue);
                } elseif ($propertyValue instanceof \BackedEnum) {
                    $serializedPropertyValue = [
                        '__class' => $propertyValue::class,
                        'value' => $propertyValue->value
                    ];
                } elseif ($propertyValue instanceof UriInterface) {
                    // prevent changing the URI's state
                    $uriClone = clone $propertyValue;
                    $serializedPropertyValue = [
                        '__class' => Uri::class,
                        'value' => (string)$uriClone
                    ];
                } else {
                    $serializedPropertyValue = [
                        '__type' => gettype($propertyValue),
                        'value' => $propertyValue
                    ];
                }

                $result[$reflectionProperty->name] = $serializedPropertyValue;
            }
        }

        return $result;
    }

    /**
     * @return array<string,mixed>
     */
    private static function serializeComponentCollection(
        ComponentCollection $componentCollection,
        \ReflectionClass $reflectionClass
    ): array {
        return [
            '__class' => get_class($componentCollection),
            'components' => array_map(
                fn (ComponentInterface|string $childComponent): array|string
                    => is_string($childComponent) ? $childComponent : self::serializeComponent($childComponent),
                $reflectionClass->getProperties()[0]->getValue($componentCollection)
            )
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function serializeCollectionType(
        ComponentInterface $componentCollection,
        \ReflectionClass $reflectionClass
    ): array {
        return [
            '__class' => get_class($componentCollection),
            $reflectionClass->getProperties()[0]->getName() => array_map(
                fn(ComponentInterface $childComponent): array => self::serializeComponent($childComponent),
                $reflectionClass->getProperties()[0]->getValue($componentCollection)
            )
        ];
    }
}
