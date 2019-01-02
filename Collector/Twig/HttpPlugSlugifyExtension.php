<?php

namespace Http\HttplugBundle\Collector\Twig;

/**
 * @author Ibrahima SOW <sowbiba@hotmail.com>
 */
class HttpPlugSlugifyExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('httplug_slugify', [$this, 'slugify'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param $string
     *
     * @return string
     */
    public function slugify($string)
    {
        return preg_replace('/[^a-zA-Z0-9_-]/u', '_', $string);
    }

    public function getName()
    {
        return 'httplug_slugify';
    }
}
