<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Infrastructure;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetVariantInterface;
use Neos\Neos\Cache\ContentCacheFlusherInterface;
use Nezaniel\ComponentView\Application\CacheTag;
use Nezaniel\ComponentView\Application\CacheTagSet;
use Nezaniel\ComponentView\Application\ComponentCache;

#[Flow\Scope('singleton')]
readonly class ComponentCacheFlusher implements ContentCacheFlusherInterface
{
    public function __construct(
        private ComponentCache $cache,
        private PersistenceManagerInterface $persistenceManager,
    ) {
    }

    public function flushNodeAggregate(
        ContentRepository $contentRepository,
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId
    ): void {
        $cacheTags = [
            CacheTag::forEverything($contentRepository->id, $contentStreamId),
            CacheTag::forNodeAggregate($contentRepository->id, $contentStreamId, $nodeAggregateId)
        ];

        $nodeAggregate = $contentRepository->getContentGraph()->findNodeAggregateById($contentStreamId, $nodeAggregateId);
        foreach (
            $this->resolveAllSuperTypeNames(
                $contentRepository->getNodeTypeManager()->getNodeType($nodeAggregate->nodeTypeName)
            ) as $nodeTypeName
        ) {
            $cacheTags[] = CacheTag::forNodeTypeName($contentRepository->id, $nodeAggregate->contentStreamId, $nodeTypeName);
        }
        $cacheTagsToFlush = new CacheTagSet(...$cacheTags);

        $cacheTagsToFlush = $cacheTagsToFlush->union($this->processAncestors($contentRepository, $nodeAggregate));

        file_put_contents(FLOW_PATH_DATA . 'Flushed_CacheTags.txt', implode("\n", $cacheTagsToFlush->toStringArray()));
        $this->cache->clearByTags($cacheTagsToFlush);
    }

    private function processAncestors(ContentRepository $contentRepository, NodeAggregate $nodeAggregate): CacheTagSet
    {
        $cacheTagsToFlush = new CacheTagSet(
            CacheTag::forAncestorNode($contentRepository->id, $nodeAggregate->contentStreamId, $nodeAggregate->nodeAggregateId)
        );

        foreach (
            $contentRepository->getContentGraph()->findParentNodeAggregates(
                $nodeAggregate->contentStreamId,
                $nodeAggregate->nodeAggregateId
            ) as $parentNodeAggregate
        ) {
            $cacheTagsToFlush = $cacheTagsToFlush->union($this->processAncestors($contentRepository, $parentNodeAggregate));
        }

        return $cacheTagsToFlush;
    }

    public function registerAssetChange(AssetInterface $asset): void
    {
        $assetIds = [$this->persistenceManager->getIdentifierByObject($asset)];
        if ($asset instanceof AssetVariantInterface) {
            $assetIds[] = $this->persistenceManager->getIdentifierByObject($asset->getOriginalAsset());
        }

        $this->cache->clearByTags(new CacheTagSet(
            CacheTag::forEverything(null, null),
            ...array_map(
                fn (string $assetId): CacheTag => CacheTag::forAsset($assetId),
                array_filter($assetIds, fn (mixed $assetId): bool => is_string($assetId))
            )
        ));
    }

    /**
     * @param NodeType $nodeType
     * @return array<string,NodeTypeName>
     */
    private function resolveAllSuperTypeNames(NodeType $nodeType): array
    {
        $superTypes = [];
        $superTypes[$nodeType->name->value] = $nodeType->name;

        foreach ($nodeType->getDeclaredSuperTypes() as $superType) {
            $superTypes = array_merge($superTypes, $this->resolveAllSuperTypeNames($superType));
        }

        return $superTypes;
    }
}
