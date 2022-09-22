<?php

/*
 * This file is part of the Nezaniel.ComponentView package
 * and taken over from PackageFactory.AtomicFusion.PresentationObjects.
 * @see https://github.com/PackageFactory/atomic-fusion-presentationobjects
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Domain;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\Context as ContentContext;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Http;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\Service\LinkingService;
use Neos\Flow\Mvc;
use Neos\Flow\Core\Bootstrap;

/**
 * The URI service
 */
final class UriService
{
    #[Flow\Inject]
    protected ResourceManager $resourceManager;

    #[Flow\Inject]
    protected LinkingService $linkingService;

    #[Flow\Inject]
    protected AssetRepository $assetRepository;

    #[Flow\Inject]
    protected Bootstrap $bootstrap;

    protected ?ControllerContext $controllerContext = null;

    public function getNodeUri(NodeInterface $documentNode, bool $absolute = false, ?string $format = null): Uri
    {
        return new Uri($this->linkingService->createNodeUri($this->getControllerContext(), $documentNode, null, $format, $absolute));
    }

    public function getResourceUri(string $packageKey, string $resourcePath): Uri
    {
        return new Uri($this->resourceManager->getPublicPackageResourceUri($packageKey, $resourcePath));
    }

    public function getAssetUri(AssetInterface $asset): Uri
    {
        $uri = $this->resourceManager->getPublicPersistentResourceUri($asset->getResource());

        return new Uri(is_string($uri) ? $uri : '#');
    }

    public function getPersistentResourceUri(PersistentResource $resource): Uri
    {
        $uri = $this->resourceManager->getPublicPersistentResourceUri($resource);

        return new Uri(is_string($uri) ? $uri : '#');
    }

    public function getControllerContext(): ControllerContext
    {
        if (is_null($this->controllerContext)) {
            $requestHandler = $this->bootstrap->getActiveRequestHandler();
            if ($requestHandler instanceof Http\RequestHandler) {
                $request = $requestHandler->getHttpRequest();
            } else {
                $request = ServerRequest::fromGlobals();
            }
            $actionRequest = Mvc\ActionRequest::fromHttpRequest($request);
            $uriBuilder = new Mvc\Routing\UriBuilder();
            $uriBuilder->setRequest($actionRequest);
            $this->controllerContext = new Mvc\Controller\ControllerContext(
                $actionRequest,
                new Mvc\ActionResponse(),
                new Mvc\Controller\Arguments(),
                $uriBuilder
            );
        }

        return $this->controllerContext;
    }

    public function resolveLinkUri(string $rawLinkUri, ContentContext $subgraph): Uri
    {
        if (\mb_substr($rawLinkUri, 0, 7) === 'node://') {
            $nodeIdentifier = \mb_substr($rawLinkUri, 7);
            $node = $subgraph->getNodeByIdentifier($nodeIdentifier);
            $linkUri = $node ? $this->getNodeUri($node) : new Uri('#');
        } elseif (\mb_substr($rawLinkUri, 0, 8) === 'asset://') {
            $assetIdentifier = \mb_substr($rawLinkUri, 8);
            /** @var ?AssetInterface $asset */
            $asset = $this->assetRepository->findByIdentifier($assetIdentifier);
            $linkUri = $asset ? $this->getAssetUri($asset) : new Uri('#');
        } elseif (\str_starts_with($rawLinkUri, 'https://') || \str_starts_with($rawLinkUri, 'http://')) {
            $linkUri = new Uri($rawLinkUri);
        } else {
            $linkUri = new Uri('#');
        }

        return $linkUri;
    }
}
