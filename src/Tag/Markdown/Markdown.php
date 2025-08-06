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

        // Update content.xml
        $content = $odt->getEntryContents('content.xml');

        // Suppress XML errors for invalid HTML because we are going to manipulate some unknown namespaces
        // This is necessary because the Markdown content may contain HTML tags that are not valid in ODT XML
        // and we want to avoid warnings or errors when loading the XML.
        libxml_use_internal_errors(true);

        // Load the content as a DOMDocument
        $dom = new \DOMDocument();
        $dom->loadXML($content);

        // Find the node containing the tag
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query("//*[text() = '$raw']");
        if (0 === $nodes->length) {
            throw new \RuntimeException("Tag '$raw' not found in content.xml");
        }

        // Iterate over all nodes that match the tag
        // and replace them with the converted Markdown content
        foreach ($nodes as $node) {
            // Create a fragment from the Markdown-converted XML
            $fragment = $dom->createDocumentFragment();
            $fragment->appendXML($value);
            // Replace the entire node (parent tag) with the fragment
            $node->parentNode->replaceChild($fragment, $node);
        }

        // Add the modified content back to the ODT
        $odt->addFromString('content.xml', $dom->saveXML());

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
