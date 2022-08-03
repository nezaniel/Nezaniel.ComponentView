<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Domain;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Service\HtmlAugmenter;

/**
 * A component for wrapping node metadata around a component
 */
#[Flow\Proxy(false)]
final class NodeMetadataWrapper implements ComponentInterface
{
    /**
     * @param array<string,mixed> $attributes
     */
    public function __construct(
        private ?array $attributes,
        private ComponentInterface|string $content
    ) {
    }

    public function __toString(): string
    {
        return $this->render();
    }

    public function render(): string
    {
        $result = (string)$this->content;
        if (is_array($this->attributes)) {
            // @todo: replace by own implementation to decouple from Fusion
            $augmenter = new HtmlAugmenter();
            return $augmenter->addAttributes($result, $this->attributes);
        }

        return $result;
    }

    public function isEmpty(): bool
    {
        return $this->content instanceof ComponentContainerInterface
            ? $this->content->isEmpty()
            : false;
    }
}
