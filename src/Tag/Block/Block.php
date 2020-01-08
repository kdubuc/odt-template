<?php

namespace Kdubuc\Odt\Tag\Block;

use Kdubuc\Odt\Tag\Tag;

abstract class Block extends Tag
{
    /*
    * Assign process priority.
    * The default priority is 10 and higher priorities are executed first.
    * The blocks have higher priority over the tags.
    */
    public function getPriority() : int
    {
        return 10;
    }
}
