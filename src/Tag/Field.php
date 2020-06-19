<?php

namespace Kdubuc\Odt\Tag;

use Kdubuc\Odt\Odt;
use Adbar\Dot as ArrayDot;

final class Field extends Tag
{
    /*
     * Regex to isolate tag inside Odt content.
     */
    protected function getRegex() : string
    {
        return "/{field:(?'key'[\w.]+)}/";
    }

    /*
     * Render process : Within odt, edit tag with data bag.
     */
    protected function render(Odt $odt, ArrayDot $data, array $tag_infos) : Odt
    {
        // Get tag informations
        $tag   = preg_quote($tag_infos[0], '/');
        $key   = $tag_infos['key'];
        $value = is_scalar($data->get($key)) ? htmlspecialchars($data->get($key)) : '';

        // Update content.xml
        $content = $odt->getEntryContents('content.xml');
        $odt->addFromString('content.xml', preg_replace("/$tag/", $value, $content));

        return $odt;
    }
}
