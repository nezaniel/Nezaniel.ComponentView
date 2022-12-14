<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Application;

use Neos\Cache\Exception;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Cache\Psr\InvalidArgumentException;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\ContentContext;
use Nezaniel\ComponentView\Infrastructure\ComponentSerializer;
use Nezaniel\ComponentView\Infrastructure\ComponentUnserializer;
use Nezaniel\ComponentView\Domain\ComponentInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * The component cache application service
 */
#[Flow\Scope('singleton')]
class ComponentCache implements CacheInterface
{
    #[Flow\Inject]
    protected ComponentUnserializer $componentUnserializer;

    public function __construct(
        private StringFrontend $cache
    ) {
    }

    public function findComponent(string $identifier, ContentContext $subgraph, bool $inBackend): ?ComponentInterface
    {
        $data = $this->get($identifier);
        if (is_string($data)) {
            $serialization = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

            return $this->componentUnserializer->unserializeComponent(
                $serialization,
                $subgraph,
                $inBackend,
                $this
            );
        }

        return null;
    }

    public function get(string $key, mixed $default = null): ?string
    {
        $data = $this->cache->get($key);
        if (is_string($data)) {
            return $data;
        }

        return null;
    }

    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null, ?CacheTags $tags = null): bool
    {
        if (!$value instanceof ComponentInterface) {
            throw new InvalidArgumentException('Cache entries must implement ' . ComponentInterface::class, 1659211321);
        }
        try {
            $serialization = ComponentSerializer::serializeComponent($value);
            $this->cache->set(
                $key,
                json_encode($serialization, JSON_THROW_ON_ERROR),
                $tags ? $tags->toStringArray() : [],
                $ttl
            );
            return true;
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e->getPrevious());
        } catch (Exception $e) {
            return false;
        }
    }

    public function delete(string $key): bool
    {
        return $this->cache->remove($key);
    }

    public function clear(): bool
    {
        $this->cache->flush();

        return true;
    }

    public function clearByTag(CacheTag $tag): int
    {
        return $this->cache->flushByTag($tag->value);
    }

    public function clearByTags(CacheTags $tags): int
    {
        return $this->cache->flushByTags($tags->toStringArray());
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        throw new \BadMethodCallException('Method ' . __METHOD__ . ' is not supported yet', 1659390465);
    }

    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        throw new \BadMethodCallException('Method ' . __METHOD__ . ' is not supported yet', 1659390465);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        throw new \BadMethodCallException('Method ' . __METHOD__ . ' is not supported yet', 1659390465);
    }

    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }
}
