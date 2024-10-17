<?php

declare(strict_types=1);

namespace Nezaniel\ComponentView\Infrastructure;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetVariantInterface;
use Neos\Neos\Fusion\Cache\CacheFlushingStrategy;
use Neos\Neos\Fusion\Cache\FlushNodeAggregateRequest;
use Neos\Neos\Fusion\Cache\FlushWorkspaceRequest;
use Nezaniel\ComponentView\Application\CacheTag;
use Nezaniel\ComponentView\Application\CacheTagSet;
use Nezaniel\ComponentView\Application\ComponentCache;

#[Flow\Scope('singleton')]
#[Flow\Aspect]
readonly class ComponentCacheFlusher
{
    public function __construct(
        private ComponentCache $cache,
        private PersistenceManagerInterface $persistenceManager,
        private ContentRepositoryRegistry $contentRepositoryRegistry,
    ) {
    }

    #[Flow\Around('method(Neos\Neos\Fusion\Cache\ContentCacheFlusher->flushWorkspace())')]
    public function flushWorkspace(JoinPointInterface $joinPoint): void
    {
        /** @var FlushWorkspaceRequest $flushWorkspaceRequest */
        $flushWorkspaceRequest = $joinPoint->getMethodArgument('flushWorkspaceRequest');

        /** @var CacheFlushingStrategy $cacheFlushingStrategy */
        $cacheFlushingStrategy = $joinPoint->getMethodArgument('cacheFlushingStrategy');

        $cacheTags = [
            CacheTag::forEverything(null, null),
            CacheTag::forEverything($flushWorkspaceRequest->contentRepositoryId, $flushWorkspaceRequest->workspaceName)
        ];

        $this->cache->clearByTags(new CacheTagSet(...$cacheTags));
    }

    #[Flow\Around('method(Neos\Neos\Fusion\Cache\ContentCacheFlusher->flushNodeAggregate())')]
    public function flushNodeAggregate(JoinPointInterface $joinPoint): void
    {
        /** @var FlushNodeAggregateRequest $flushNodeAggregateRequest */
        $flushNodeAggregateRequest = $joinPoint->getMethodArgument('flushNodeAggregateRequest');

        /** @var CacheFlushingStrategy $cacheFlushingStrategy */
        $cacheFlushingStrategy = $joinPoint->getMethodArgument('cacheFlushingStrategy');

        $cacheTags = [
            CacheTag::forEverything(null, null),
            CacheTag::forEverything(
                $flushNodeAggregateRequest->contentRepositoryId,
                $flushNodeAggregateRequest->workspaceName
            ),
            CacheTag::forNodeAggregate(
                $flushNodeAggregateRequest->contentRepositoryId,
                $flushNodeAggregateRequest->workspaceName,
                $flushNodeAggregateRequest->nodeAggregateId
            )
        ];
        $cacheTags = array_merge($cacheTags, array_map(
            fn (NodeAggregateId $ancestorAggregateId): CacheTag => CacheTag::forNodeAggregate(
                $flushNodeAggregateRequest->contentRepositoryId,
                $flushNodeAggregateRequest->workspaceName,
                $ancestorAggregateId
            ),
            array_values(iterator_to_array($flushNodeAggregateRequest->ancestorNodeAggregateIds)),
        ));

        $contentRepository = $this->contentRepositoryRegistry->get($flushNodeAggregateRequest->contentRepositoryId);
        $contentGraph = $contentRepository->getContentGraph($flushNodeAggregateRequest->workspaceName);
        $nodeAggregate = $contentGraph->findNodeAggregateById($flushNodeAggregateRequest->nodeAggregateId);
        if ($nodeAggregate) {
            $nodeType = $contentRepository->getNodeTypeManager()->getNodeType($nodeAggregate->nodeTypeName);
            foreach (
                $nodeType
                    ? $this->resolveAllSuperTypeNames($nodeType)
                    : [] as $nodeTypeName
            ) {
                $cacheTags[] = CacheTag::forNodeTypeName(
                    $flushNodeAggregateRequest->contentRepositoryId,
                    $flushNodeAggregateRequest->workspaceName,
                    $nodeTypeName
                );
            }
        }

        $this->cache->clearByTags(new CacheTagSet(...$cacheTags));
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
