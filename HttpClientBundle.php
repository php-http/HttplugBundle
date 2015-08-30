<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Http\ClientBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Cmf\Bundle\CoreBundle\DependencyInjection\Compiler\RequestAwarePass;
use Symfony\Cmf\Bundle\CoreBundle\DependencyInjection\Compiler\AddPublishedVotersPass;

class HttpClientBundle extends Bundle
{
}
