<?php

namespace EditorBundle\Twig\Extension;

use Twig_Extension_GlobalsInterface;

/**
 * {@inheritDoc}
 */
class EditorUtilsExtension extends \Twig_Extension implements Twig_Extension_GlobalsInterface
{

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('ltrim', [$this, 'ltrim']),
            new \Twig_SimpleFilter('title', [$this, 'title']),
        ];
    }

    /**
     * @param $string
     * @param $key
     * @return string
     */
    function ltrim($string, $key)
    {
        return ltrim($string, $key);
    }

    /**
     * @param $string
     * @return string
     */
    function title($string)
    {
        return ucfirst(str_replace('_', ' ', $string));
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'EditorBundle:EditorUtilsExtension';
    }

    /**
     * {@inheritDoc}
     */
    public function getGlobals()
    {
        return [];
    }
}
