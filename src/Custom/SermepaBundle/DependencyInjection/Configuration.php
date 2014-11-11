<?php

namespace Custom\SermepaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;


class Configuration
{
    public function getConfigTree()
    {
        $tb = new TreeBuilder();

        return $tb
            ->root('jms_payment_sermepa', 'array')
                ->children()
                    ->scalarNode('username')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('password')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('terminal')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('return_url')->defaultNull()->end()
                    ->scalarNode('cancel_url')->defaultNull()->end()
                    ->booleanNode('debug')->defaultValue('%kernel.debug%')->end()
                ->end()
            ->end()
            ->buildTree();
    }
}