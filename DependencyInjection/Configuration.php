<?php

namespace Fza\FitSmsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    private $debug;

    public function  __construct($debug)
    {
        $this->debug = (bool) $debug;
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('fza_fit_sms');

        $rootNode
            ->children()
                ->booleanNode('debug_test')->defaultValue(true)->end()
                ->scalarNode('gateway_uri')->defaultValue('https://gateway.fitsms.de/sms/http2sms.jsp')->end()
                ->scalarNode('max_sms_part_count')->defaultValue(6)->end()
                ->scalarNode('default_intl_prefix')->defaultValue(1)->end()
                ->booleanNode('tracking')->defaultValue(true)->end()
                ->scalarNode('username')->isRequired()->end()
                ->scalarNode('password')->isRequired()->end()
                ->booleanNode('numlock')->defaultValue(false)->end()
                ->booleanNode('iplock')->defaultValue(false)->end()
                ->scalarNode('from')->defaultValue(null)->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
