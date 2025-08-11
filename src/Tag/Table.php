<?php

namespace Kdubuc\Odt\Tag;

use Kdubuc\Odt\Odt;
use Adbar\Dot as ArrayDot;

final class Table extends Tag
{
    /*
     * Regex to isolate tag inside Odt content.
     */
    protected function getRegex() : string
    {
        return "/\[TABLE\s(?'key'[\w.]+)\](?'content'.*?)\[\/TABLE \k'key'\]/s";
    }

    /*
     * Render process : Within odt, edit tag with data bag.
     */
    protected function render(Odt $odt, ArrayDot $data, array $tag_infos) : Odt
    {
        // Get all odt content
        $content = $odt->getEntryContents('content.xml');

        // Get the key and content from the tag infos
        $key        = $tag_infos['key'];
        $startBlock = "[TABLE $key]";
        $endBlock   = "[/TABLE $key]";

        // Load the content as XML
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadXML($content);

        // Find all last row nodes in the each table in the block
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query(
            "//*[text() = '$startBlock']/following-sibling::*[following-sibling::*[text() = '$endBlock'] and preceding-sibling::*[text() = '$startBlock']]/table:table-row[last()]"
        );

        // If no nodes found, throw an exception
        if (0 === $nodes->length) {
            throw new \RuntimeException("No table block found in the content for key '$key'");
        }

        // Iterate over the nodes and apply the table routine
        foreach ($nodes as $node) {
            // Ensure the node owner Document is a valid DOMDocument
            $ownerDocument = $node->ownerDocument;
            if (!$ownerDocument instanceof \DOMDocument) {
                throw new \RuntimeException('Node does not have a valid owner document.');
            }

            // Get the raw XML content of the node thanks to ownerDocument
            $baseXml = $ownerDocument->saveXml($node);
            if (false === $baseXml || empty($baseXml)) {
                throw new \RuntimeException('Failed to save XML for node: '.json_encode($node));
            }

            // Replace the placeholders with actual data
            $newXml = '';
            foreach ($data->get($key, []) as $row) {
                $odt->addFromString('content.xml', $baseXml);
                $newXml .= $odt->render([$row], [], ['page_break' => false])->getEntryContents('content.xml');
            }

            // If no new XML content is generated, skip the node
            if (!empty($newXml)) {
                $fragment = $dom->createDocumentFragment();
                $fragment->appendXML($newXml);
                $node->parentNode->appendChild($fragment);
            }

            $node->parentNode->removeChild($node);
        }

        // Add the modified content back to the ODT
        $odt->addFromString('content.xml', $dom->saveXML());

        // Remove the start and end tags in the ODT content
        $odt->replace($startBlock, '');
        $odt->replace($endBlock, '');

        return $odt;
    }
}
