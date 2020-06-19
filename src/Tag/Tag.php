<?php

namespace Kdubuc\Odt\Tag;

use Kdubuc\Odt\Odt;
use Adbar\Dot as ArrayDot;

abstract class Tag
{
    /*
     * Regex to isolate tag inside Odt content.
     */
    abstract public function getRegex() : string;

    /*
     * Render process using regex and tag informations.
     */
    abstract public function render(Odt $odt, ArrayDot $data, array $tag_infos) : Odt;
}
