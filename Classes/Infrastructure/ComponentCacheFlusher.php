<?php

declare(strict_types=1);

namespace Nezaniel\ComponentView\Infrastructure;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetVariantInterface;
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

    #[Flow\Around('method(Neos\Neos\Fusion\Cache\ContentCacheFlusher->flushNodeAggregate())')]
    public function flushNodeAggregate(JoinPointInterface $joinPoint): void
    {
        /** @var ContentRepository $contentRepository */
        $contentRepository = $joinPoint->getMethodArgument('contentRepository');
        /** @var WorkspaceName $workspaceName */
        $workspaceName = $joinPoint->getMethodArgument('workspaceName');
        /** @var NodeAggregateId $nodeAggregateId */
        $nodeAggregateId = $joinPoint->getMethodArgument('nodeAggregateId');

        $cacheTags = [
            CacheTag::forEverything(null, null),
            CacheTag::forEverything($contentRepository->id, $workspaceName),
            CacheTag::forNodeAggregate($contentRepository->id, $workspaceName, $nodeAggregateId)
        ];
        $ancestorNodeAggregates = $contentRepository->getContentGraph($workspaceName)->findParentNodeAggregates($nodeAggregateId);
        $cacheTags = array_merge($cacheTags, array_map(
            fn (NodeAggregate $ancestorAggregate): CacheTag => CacheTag::forAncestorNode($contentRepository->id, $workspaceName, $ancestorAggregate->nodeAggregateId),
            iterator_to_array($ancestorNodeAggregates),
        ));

        $contentRepository = $this->contentRepositoryRegistry->get($contentRepository->id);
        $nodeAggregate = $contentRepository->getContentGraph($workspaceName)->findNodeAggregateById($nodeAggregateId);
        if ($nodeAggregate) {
            $nodeType = $contentRepository->getNodeTypeManager()->getNodeType($nodeAggregate->nodeTypeName);
            foreach (
                $nodeType
                    ? $this->resolveAllSuperTypeNames($nodeType)
                    : [] as $nodeTypeName
            ) {
                $cacheTags[] = CacheTag::forNodeTypeName($contentRepository->id, $workspaceName, $nodeTypeName);
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
