<?php

namespace Kdubuc\Odt\Tag;

use Kdubuc\Odt\Odt;
use Adbar\Dot as ArrayDot;

final class Conditional extends Tag
{
    /*
     * Regex to isolate tag inside Odt content.
     */
    public function getRegex() : string
    {
        return "/\[IF\s(?'key'[\w.]+)\](?'content'.*?)\[\/IF \k'key'\]/s";
    }

    /*
     * Replace tag with using regex and data variable
     */
    public function render(Odt $odt, ArrayDot $data, array $tag_infos) : Odt
    {
        // Get block info from tag
        $block         = preg_quote($tag_infos[0], '/');
        $key           = $tag_infos['key'];
        $block_content = $tag_infos['content'];

        // If the value is true, display the block content
        $content = $odt->getEntryContents('content.xml');
        $odt->addFromString('content.xml', (string) preg_replace("/$block/", (bool) $data->get($key) ? $block_content : '', $content));

        return $odt;
    }
}
