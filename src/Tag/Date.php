<?php

namespace Kdubuc\Odt\Tag;

use Kdubuc\Odt\Odt;
use Adbar\Dot as ArrayDot;

final class Date extends Tag
{
    /*
     * Regex to isolate tag inside Odt content.
     */
    public function getRegex() : string
    {
        return "/{date:(?'key'[\w.]+)}/";
    }

    /*
     * Render process using regex and tag informations.
     */
    public function render(Odt $odt, ArrayDot $data, array $tag_infos) : Odt
    {
        // Get tag informations
        $tag       = preg_quote($tag_infos[0], '/');
        $key       = $tag_infos['key'];

        // Get timestamp (if possible) and parse locale string
        $timestamp = (new \Datetime($data->get($key)))->getTimestamp();
        $date      = explode('|', strftime('%e|%m|%Y', $timestamp));
        $month     = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        $value     = $date[0].' '.$month[(int) ($date[1])].' '.$date[2];

        // Update content.xml
        $content = $odt->getEntryContents('content.xml');
        $odt->addFromString('content.xml', preg_replace("/$tag/", $value, $content));

        return $odt;
    }
}
