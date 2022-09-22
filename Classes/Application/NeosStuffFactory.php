<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Application;

use Neos\ContentRepository\Domain\Model\Node;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Ui\Domain\Service\ConfigurationRenderingService;
use Neos\Neos\Ui\Fusion\Helper\StaticResourcesHelper;

/**
 * The factory to create components necessary for Neos' inline editing
 */
#[Flow\Scope('singleton')]
final class NeosStuffFactory extends AbstractComponentFactory
{
    #[Flow\Inject]
    protected ConfigurationRenderingService $configurationRenderingService;

    /**
     * @Flow\InjectConfiguration(package="Neos.Neos.Ui.documentNodeInformation")
     * @var array
     */
    protected $settings;

    public function getHeadStuff(bool $inBackend, Node $documentNode, Node $site): ?string
    {
        if (!$inBackend) {
            return null;
        }

        $configuration = $this->configurationRenderingService->computeConfiguration(
            $this->settings,
            [
                'documentNode' => $documentNode,
                'site' => $site,
                'controllerContext' => $this->uriService->getControllerContext()
            ]
        );

        $compiledResourcePackageKey = (new StaticResourcesHelper())->compiledResourcePackage();

        return '
            <script>window[\'@Neos.Neos.Ui:DocumentInformation\']=' . json_encode($configuration) . '</script>
            <script>window.neos = window.parent.neos;</script>
            <script src="' . $this->uriService->getResourceUri($compiledResourcePackageKey, 'JavaScript/Vendor.js') . '"></script>
            <script src="' . $this->uriService->getResourceUri($compiledResourcePackageKey, 'JavaScript/Guest.js') . '"></script>
            <link rel="stylesheet" href="' . $this->uriService->getResourceUri($compiledResourcePackageKey, 'Styles/Host.css') . '">
        ';
    }

    public function getBodyStuff(bool $inBackend): ?string
    {
        return $inBackend
            ? '
                <div id="neos-backend-container"></div>
                <script type="application/javascript">
                    document.addEventListener("DOMContentLoaded", function () {
                        var event = new CustomEvent("Neos.Neos.Ui.ContentReady");
                        window.parent.document.dispatchEvent(event);
                    });
                </script>
            '
            : null;
    }
}
