<?php

declare(strict_types=1);

namespace Nezaniel\ComponentView\Domain;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Http;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Flow\Mvc;
use Neos\Flow\Core\Bootstrap;
use Neos\Neos\FrontendRouting\NodeUriBuilderFactory;
use Neos\Neos\FrontendRouting\Options;
use Nezaniel\ComponentView\Application\CacheTag;
use Nezaniel\ComponentView\Application\CacheTagSet;
use Psr\Http\Message\UriInterface;

/**
 * The URI service
 */
#[Flow\Scope('singleton')]
final class UriService
{
    #[Flow\Inject]
    protected ResourceManager $resourceManager;

    #[Flow\Inject]
    protected AssetRepository $assetRepository;

    #[Flow\Inject]
    protected Bootstrap $bootstrap;

    #[Flow\Inject]
    protected NodeUriBuilderFactory $nodeUriBuilderFactory;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    protected ?ControllerContext $controllerContext = null;

    public function setControllerContext(ControllerContext $controllerContext): void
    {
        $this->controllerContext = $controllerContext;
    }

    public function getNodeUri(Node $documentNode, bool $absolute = false, ?string $format = null): UriInterface
    {
        $options = Options::createEmpty();
        if ($absolute) {
            $options = $options->withForceAbsolute();
        }
        if ($format) {
            $options = $options->withCustomFormat($format);
        }
        return $this->nodeUriBuilderFactory->forActionRequest($this->getControllerContext()->getRequest())
            ->uriFor(NodeAddress::fromNode($documentNode), $options);
    }

    public function getBackendNodeUri(
        Node $documentNode,
        ?WorkspaceName $workspaceName = null,
        bool $absolute = false,
        ?string $format = null
    ): UriInterface {
        assert($this->controllerContext instanceof ControllerContext);
        $uriBuilder = clone $this->controllerContext->getUriBuilder();
        $uriBuilder->reset();
        $uriBuilder->setCreateAbsoluteUri($absolute);
        $uriBuilder->setFormat($format ?: $this->controllerContext->getUriBuilder()->getFormat());

        $nodeAddress = NodeAddress::fromNode($documentNode);
        if ($workspaceName) {
            $nodeAddress = NodeAddress::create(
                $nodeAddress->contentRepositoryId,
                $workspaceName,
                $nodeAddress->dimensionSpacePoint,
                $nodeAddress->aggregateId
            );
        }

        return new Uri(
            $uriBuilder->uriFor(
                'index',
                [
                    'node' => $nodeAddress->toJson(),
                ],
                'Backend',
                'Neos.Neos.Ui',
            )
        );
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

    public function resolveLinkUri(string $rawLinkUri, ContentSubgraphInterface $subgraph, ?CacheTagSet &$cacheTags): UriInterface
    {
        if (\mb_substr($rawLinkUri, 0, 7) === 'node://') {
            $nodeIdentifier = \mb_substr($rawLinkUri, 7);
            $node = $subgraph->findNodeById(NodeAggregateId::fromString($nodeIdentifier));
            $linkUri = $node ? $this->getNodeUri($node, true) : new Uri('#');
            if ($node && $cacheTags) {
                $cacheTags = $cacheTags->add(CacheTag::forNodeAggregateFromNode($node));
            }
        } elseif (\mb_substr($rawLinkUri, 0, 8) === 'asset://') {
            $assetIdentifier = \mb_substr($rawLinkUri, 8);
            /** @var ?Asset $asset */
            $asset = $this->assetRepository->findByIdentifier($assetIdentifier);
            $linkUri = $asset ? $this->getAssetUri($asset) : new Uri('#');
            if ($asset && $cacheTags) {
                $cacheTags = $cacheTags->add(CacheTag::forAsset($asset->getIdentifier()));
            }
        } elseif (\str_starts_with($rawLinkUri, 'https://') || \str_starts_with($rawLinkUri, 'http://')) {
            $linkUri = new Uri($rawLinkUri);
        } else {
            $linkUri = new Uri('#');
        }

        return $linkUri;
    }
}
