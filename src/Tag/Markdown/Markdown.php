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
        return "/(<text[^>]*>){md:(?'key'[\w.]+)}(<\/text[^>]*>)/s";
    }

    /*
     * Render process : Within odt, edit tag with data bag.
     */
    protected function render(Odt $odt, ArrayDot $data, array $tag_infos) : Odt
    {
        // Get tag informations
        $tag   = preg_quote($tag_infos[0], '/');
        $key   = $tag_infos['key'];
        $value = \is_scalar($data->get($key)) ? htmlspecialchars($data->get($key)) : '';

        // Convert Markdown to HTML
        $value = $this->convertMarkdownToOdt($value);

        // Update content.xml
        $content = $odt->getEntryContents('content.xml');
        $odt->addFromString('content.xml', preg_replace("/$tag/", $value, $content));

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
