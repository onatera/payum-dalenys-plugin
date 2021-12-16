<?php

declare(strict_types=1);

namespace Onatera\PayumDalenysPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @psalm-suppress UnusedVariable
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('onatera_payum_dalenys');
        $rootNode = $treeBuilder->getRootNode();

        return $treeBuilder;
    }
}
