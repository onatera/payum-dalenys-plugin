<?php

declare(strict_types=1);

namespace Onatera\PayumDalenysPlugin;

use Onatera\PayumDalenysPlugin\DependencyInjection\RequestStackAwareCompilerPass;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class OnateraPayumDalenysPlugin extends Bundle
{
    use SyliusPluginTrait;

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RequestStackAwareCompilerPass());
    }
}
