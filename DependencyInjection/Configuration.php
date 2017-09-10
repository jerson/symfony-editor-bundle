<?php

namespace EditorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('editor');

        $rootNode
            ->children()
            ->arrayNode('parameter')
            ->prototype('array')
            ->children()
            ->scalarNode('default')->end()
            ->scalarNode('group')->end()
            ->scalarNode('type')->end()
            ->scalarNode('help')->end()
            ->arrayNode('options')->prototype('scalar')->end()->end()
            ->end()
            ->end()
            ->end()
            ->arrayNode('router')
            ->prototype('array')
            ->children()
            ->scalarNode('resource')->end()
            ->scalarNode('type')->end()
            ->end()
            ->end()
            ->end()
            ->arrayNode('translator')
            ->prototype('array')
            ->children()
            ->scalarNode('bundle')->end()
            ->scalarNode('domain')->end()
            ->scalarNode('locales')->end()
            ->end()
            ->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}
