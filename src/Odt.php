<?php

namespace Kdubuc\Odt;

use DOMDocument;
use PhpZip\ZipFile as Zip;

/*
 * Render ODT with a ZIP handler (remember, OpenDocument is basically a zip file
 * according to the specs https://en.wikipedia.org/wiki/OpenDocument_technical_specification).
 */
final class Odt extends Zip
{
    /*
     * Start the rendering process with data array to fill document tags.
     */
    public function render(array $pages, array $pipeline = [], array $options = []) : self
    {
        // Init XML I/O
        $xml = new DOMDocument();

        // Init options array with defaults values
        $options = array_merge([
            'page_break' => true,
        ], $options);

        // Default pipeline renderer
        // The blocks (like segment and conditional) have higher priority over the simple tags, because they
        // must be processed BEFORE any fields for correct context assignation.
        if (empty($pipeline)) {
            $pipeline = [
                new Tag\Segment(),
                new Tag\Conditional(),
                new Tag\Image(),
                new Tag\Qrcode(),
                new Tag\Date(),
                new Tag\Field(),
            ];
        }

        // Build page break style
        $pagebreak_style = $xml->createElement('style:style');
        $pagebreak_style->setAttribute('style:name', 'pagebreak');
        $pagebreak_style->setAttribute('style:family', 'paragraph');
        $pagebreak_style_properties = $xml->createElement('style:paragraph-properties');
        $pagebreak_style_properties->setAttribute('fo:break-before', 'page');
        $pagebreak_style->appendChild($pagebreak_style_properties);

        // Append the style in the ODT
        $xml->loadXML($this->getEntryContents('styles.xml'));
        $xml->getElementsByTagName('styles')->item(0)->appendChild($xml->importNode($pagebreak_style, true));
        $this->addFromString('styles.xml', $xml->saveHTML());

        // Build page break element for future use
        $pagebreak = $xml->createElement('text:p');
        $pagebreak->setAttribute('text:style-name', 'pagebreak');

        // Get template body (disable error reporting)
        @$xml->loadXML($this->getEntryContents('content.xml'));
        $template = $xml->getElementsByTagName('text')->item(0);

        // Keep current odt reference for the rendering process
        $odt = $this;

        // Build all document pages
        foreach ($pages as $index => $page) {
            // Duplicate and append new page using page break element if index > 0
            if ($index > 0 && true === $options['page_break']) {
                $xml->loadXML($odt->getEntryContents('content.xml'));
                $xml->getElementsByTagName('text')->item(0)->appendChild($xml->importNode($pagebreak, true));
                foreach ($template->childNodes as $new_page_child_node) {
                    $xml->getElementsByTagName('text')->item(0)->appendChild($xml->importNode($new_page_child_node, true));
                }
                $odt->addFromString('content.xml', $xml->saveXML());
            }

            // ODT multiple rendering pass (pipeline process)
            foreach ($pipeline as $rendering_process) {
                $odt = $rendering_process->apply($odt, $page);
            }
        }

        return $odt;
    }
}
