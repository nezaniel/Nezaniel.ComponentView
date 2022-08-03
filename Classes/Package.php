<?php

declare(strict_types=1);

namespace Nezaniel\ComponentView;

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Media\Domain\Service\AssetService;
use Neos\Neos\Domain\Service\SiteImportService;
use Neos\Neos\Domain\Service\SiteService;
use Neos\Neos\Service\PublishingService;
use Neos\ContentRepository\Domain\Model\Node;
use Nezaniel\ComponentView\Infrastructure\ComponentCacheFlusher;

/**
 * The Nezaniel.ComponentView Package
 */
class Package extends BasePackage
{
    public function boot(Bootstrap $bootstrap): void
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();

        $dispatcher->connect(Node::class, 'nodeUpdated', ComponentCacheFlusher::class, 'whenNodeWasUpdated', false);
        $dispatcher->connect(Node::class, 'nodeAdded', ComponentCacheFlusher::class, 'whenNodeWasAdded', false);
        $dispatcher->connect(Node::class, 'nodeRemoved', ComponentCacheFlusher::class, 'whenNodeWasRemoved', false);
        $dispatcher->connect(Node::class, 'beforeNodeMove', ComponentCacheFlusher::class, 'whenNodeWasMoved', false);

        $dispatcher->connect(PublishingService::class, 'nodePublished', ComponentCacheFlusher::class, 'whenNodeWasPublished', false);
        $dispatcher->connect(PublishingService::class, 'nodeDiscarded', ComponentCacheFlusher::class, 'whenNodeWasDiscarded', false);

        $dispatcher->connect(AssetService::class, 'assetUpdated', ComponentCacheFlusher::class, 'whenAssetWasChanged', false);

        $dispatcher->connect(SiteService::class, 'sitePruned', ComponentCacheFlusher::class, 'whenSiteWasPruned');
        $dispatcher->connect(SiteImportService::class, 'siteImported', ComponentCacheFlusher::class, 'whenSiteWasImported');
    }
}
