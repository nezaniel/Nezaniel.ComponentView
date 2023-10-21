<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView;

use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Flow\SignalSlot\Dispatcher;
use Neos\Media\Domain\Service\AssetService;
use Nezaniel\ComponentView\Infrastructure\ComponentCacheFlusher;

/**
 * The Nezaniel.ComponentView Package
 */
class Package extends BasePackage
{
    public function boot(Bootstrap $bootstrap): void
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();

        $this->registerCacheSignals($dispatcher);
    }

    private function registerCacheSignals(Dispatcher $dispatcher): void
    {
        $dispatcher->connect(
            AssetService::class,
            'assetUpdated',
            ComponentCacheFlusher::class,
            'registerAssetChange',
            false
        );
    }
}
