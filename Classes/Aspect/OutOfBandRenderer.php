<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Aspect;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;

/**
 * An aspect intercepting Neos' attempt to do anything fusiony in ReloadContentOutOfBand
 *
 * @see ReloadContentOutOfBand
 */
#[Flow\Aspect]
class OutOfBandRenderer
{
    #[Flow\Around('method(protected Neos\Neos\Ui\Domain\Model\Feedback\Operations\ReloadContentOutOfBand->renderContent())')]
    public function renderContent(JoinPointInterface $joinPoint): string
    {
        return '';
    }
}
