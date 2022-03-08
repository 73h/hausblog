<?php

namespace src\utils;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class HTMLPurifierExtension extends AbstractExtension
{

    public function getFilters(): array
    {
        return [new TwigFilter('html_purifier', [$this, 'purify'], ['is_safe' => ['html']])];
    }

    public static function purify($text): string
    {
        $elements = array(
            'p',
            'br',
            'small',
            'strong', 'b',
            'em', 'i',
            'strike',
            'sub', 'sup',
            'ins', 'del',
            'ol', 'ul', 'li',
            'dl', 'dd', 'dt',
            'pre', 'code', 'samp', 'kbd',
            'q', 'blockquote', 'abbr', 'cite',
            'table', 'thead', 'tbody', 'th', 'tr', 'td',
            'a[href | target | rel | id]',
            'img[src | title | alt | width | height | style | class]'
        );

        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', implode(',', $elements));

        $purifier = new \HTMLPurifier($config);
        return $purifier->purify($text);
    }

}
