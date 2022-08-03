<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Infrastructure;

use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\AssetInterface;
use Nezaniel\ComponentView\Application\CacheTag;
use Nezaniel\ComponentView\Application\CacheTags;
use Nezaniel\ComponentView\Application\ComponentCache;

/**
 * The component cache flusher infrastructure service
 */
#[Flow\Scope('singleton')]
class ComponentCacheFlusher
{
    #[Flow\Inject]
    protected ComponentCache $cache;

    #[Flow\Inject]
    protected WorkspaceRepository $workspaceRepository;

    #[Flow\Inject]
    protected NodeTypeManager $nodeTypeManager;

    #[Flow\Inject]
    protected PersistenceManagerInterface $persistenceManager;

    private CacheTags $cacheTagsToFlush;

    /**
     * @var array<string,string>
     */
    private array $workspaceGraph;

    public function __construct(
    ) {
        $this->cacheTagsToFlush = new CacheTags();
        $this->workspaceGraph = [];
    }

    public function initializeObject(): void
    {
        $workspaces = $this->workspaceRepository->findAll();
        foreach ($workspaces as $workspace) {
            /** @var Workspace $workspace */
            $baseWorkspace = $workspace->getBaseWorkspace();
            if ($baseWorkspace instanceof Workspace) {
                $this->workspaceGraph[$baseWorkspace->getName()] = $workspace->getName();
            }
        }
    }

    public function whenNodeWasUpdated(Node $node): void
    {
        $this->handleNodeChange($node);
    }

    public function whenNodeWasAdded(Node $node): void
    {
        $this->handleNodeChange($node);
    }

    public function whenNodeWasRemoved(Node $node): void
    {
        $this->handleNodeChange($node);
    }

    public function whenNodeWasMoved(Node $node): void
    {
        $this->handleNodeChange($node);
    }

    public function whenNodeWasPublished(Node $node, ?Workspace $targetWorkspace = null): void
    {
        $this->handleNodeChange($node, $targetWorkspace?->getName());
    }

    public function whenAssetWasChanged(AssetInterface $asset): void
    {
        $this->handleAssetChange($asset);
    }

    public function whenSiteWasPruned(): void
    {
        $this->cache->clear();
    }

    public function whenSiteWasImported(): void
    {
        $this->cache->clear();
    }

    private function handleAssetChange(AssetInterface $asset): void
    {
        $cacheTagsToFlush = [CacheTag::forEverything(null)];
        $assetIdentifier = $this->persistenceManager->getIdentifierByObject($asset);
        $cacheTagsToFlush[] = CacheTag::forAsset($assetIdentifier, null);
        foreach ($this->workspaceRepository->findAll() as $workspace) {
            /** @var Workspace $workspace */
            $cacheTagsToFlush = [CacheTag::forEverything($workspace->getName())];
            $cacheTagsToFlush[] = CacheTag::forAsset($assetIdentifier, $workspace->getName());
        }

        $this->cacheTagsToFlush = $this->cacheTagsToFlush->union(new CacheTags(...$cacheTagsToFlush));
    }

    private function handleNodeChange(Node $node, ?string $workspaceName = null): void
    {
        $cacheTagsToFlush = [
            CacheTag::forEverything(null),
            CacheTag::forNode($node, null)
        ];
        $workspaceName = $workspaceName ?: $node->getContext()->getWorkspace()->getName();
        $nodeTypes = $this->resolveAllSuperTypes($node->getNodeType());

        while ($workspaceName) {
            $cacheTagsToFlush[] = CacheTag::forEverything($workspaceName);
            $cacheTagsToFlush[] = CacheTag::forNode($node, $workspaceName);
            $ancestor = $node;
            while ($ancestor) {
                $cacheTagsToFlush[] = CacheTag::forAncestorNode($ancestor, null);
                $cacheTagsToFlush[] = CacheTag::forAncestorNode($ancestor, $workspaceName);
                $ancestor = $ancestor->getParent();
            }
            foreach ($nodeTypes as $nodeType) {
                $cacheTagsToFlush[] = CacheTag::forNodeTypeName(NodeTypeName::fromString($nodeType->getName()), null);
                $cacheTagsToFlush[] = CacheTag::forNodeTypeName(
                    NodeTypeName::fromString($nodeType->getName()),
                    $workspaceName
                );
            }

            $workspaceName = $this->workspaceGraph[$workspaceName] ?? null;
        }

        $this->cacheTagsToFlush = $this->cacheTagsToFlush->union(new CacheTags(...$cacheTagsToFlush));
    }

    /**
     * @param NodeType $nodeType
     * @return array<string,NodeType>
     */
    private function resolveAllSuperTypes(NodeType $nodeType): array
    {
        $superTypes = [];
        if (!$nodeType->isAbstract()) {
            $superTypes[$nodeType->getName()] = $nodeType;
        }

        foreach ($this->nodeTypeManager->getSubNodeTypes($nodeType->getName(), true) as $superType) {
            $superTypes = array_merge($superTypes, $this->resolveAllSuperTypes($superType));
        }

        return $superTypes;
    }

    public function shutdownObject(): void
    {
        $this->cache->clearByTags($this->cacheTagsToFlush);
    }
}
