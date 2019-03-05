<?php

namespace Http\HttplugBundle\Collector\Twig;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class HttpMessageMarkupExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('httplug_markup', [$this, 'markup'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param string $message http message
     */
    public function markup($message)
    {
        $safeMessage = htmlentities($message);
        $parts = preg_split('|\\r?\\n\\r?\\n|', $safeMessage, 2);

        if (!isset($parts[1])) {
            // This is not a HTTP message
            return $safeMessage;
        }

        if (empty($parts[1])) {
            $parts[1] = '(This message has no captured body)';
        }

        // make header names bold
        $headers = preg_replace("|\n(.*?): |si", "\n<b>$1</b>: ", $parts[0]);

        return sprintf("%s\n\n<div class='httplug-http-body httplug-hidden'>%s</div>", $headers, $parts[1]);
    }

    public function getName()
    {
        return 'httplug.message_markup';
    }
}
