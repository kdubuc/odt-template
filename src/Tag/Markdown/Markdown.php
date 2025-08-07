<?php

namespace Kdubuc\Odt\Tag\Markdown;

use Kdubuc\Odt\Odt;
use Kdubuc\Odt\Tag\Tag;
use Adbar\Dot as ArrayDot;

final class Markdown extends Tag
{
    /*
     * Regex to isolate tag inside Odt content.
     */
    protected function getRegex() : string
    {
        return "/{md:(?'key'[\w.]+)}/s";
    }

    /*
     * Render process : Within odt, edit tag with data bag.
     */
    protected function render(Odt $odt, ArrayDot $data, array $tag_infos) : Odt
    {
        // Get tag informations
        $raw   = $tag_infos[0];
        $key   = $tag_infos['key'];
        $value = \is_scalar($data->get($key)) ? htmlspecialchars($data->get($key)) : '';

        // Convert Markdown to HTML
        $value = $this->convertMarkdownToOdt($value);

        // Add the modified content back to the ODT
        $odt->replace($raw, $value);

        return $odt;
    }

    /*
     * Convert Markdown to ODT format.
     */
    private function convertMarkdownToOdt(string $markdown) : string
    {
        $environment = new \League\CommonMark\Environment\Environment();

        $environment->addExtension(new OpenDocumentExtension());
        $environment->addExtension(new \League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension());

        $converter = new \League\CommonMark\MarkdownConverter($environment);

        return $converter->convert($markdown)->getContent();
    }
}
