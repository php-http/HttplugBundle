<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Collector\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class HttpMessageMarkupExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getFilters()
    {
        return [
            new TwigFilter('httplug_markup', [$this, 'markup'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param string $message http message
     */
    public function markup($message)
    {
        @trigger_error('"httplug_markup" twig extension is deprecated since version 1.17 and will be removed in 2.0. Use "@Httplug/http_message.html.twig" template instead.', E_USER_DEPRECATED);

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
