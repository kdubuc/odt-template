<?php

namespace Kdubuc\Odt\Tag;

use Kdubuc\Odt\Odt;
use Adbar\Dot as ArrayDot;

abstract class Tag
{
    /*
     * Regex to isolate tag inside Odt content.
     */
    abstract protected function getRegex() : string;

    /*
     * Render process : Within odt, edit tag with data bag.
     */
    abstract protected function render(Odt $odt, ArrayDot $data, array $tag_infos) : Odt;

    /*
     * Render process using regex and data bag.
     */
    public function apply(Odt $odt, array $data = []) : Odt
    {
        // Provide an easy access to data with dot notation
        $data = dot($data);

        // Catch all string matches tag's regex to isolate rendering action
        preg_match_all($this->getRegex(), $odt->getEntryContents('content.xml'), $tags_infos, \PREG_SET_ORDER);

        // Apply render process for all tags found
        foreach ($tags_infos as $tag_info) {
            $odt = $this->render($odt, $data, $tag_info);
        }

        return $odt;
    }
}
