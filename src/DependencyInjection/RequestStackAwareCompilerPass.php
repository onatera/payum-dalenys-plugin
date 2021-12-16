<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Onatera\PayumDalenysPlugin\DependencyInjection;

use Onatera\PayumDalenysPlugin\Payum\RequestStackAwareInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RequestStackAwareCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('payum.action', true) as $serviceId => $tags) {
            $definition = $container->getDefinition($serviceId);

            if (is_subclass_of($definition->getClass(), RequestStackAwareInterface::class)) {
                $definition->addMethodCall('setRequestStack', [new Reference('request_stack')]);
            }
        }
    }
}
