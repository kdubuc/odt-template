<?php

namespace Kdubuc\Odt;

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
        $xml = new \DOMDocument();

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
                new Tag\Table(),
                new Tag\Conditional(),
                new Tag\Image(),
                new Tag\Qrcode(),
                new Tag\Date(),
                new Tag\Field(),
                new Tag\Markdown\Markdown(),
            ];
        }

        // Load ODT content (disable error reporting)
        @$xml->loadXML($this->getEntryContents('content.xml'));

        // Get template body
        $template = $xml->getElementsByTagName('text')->item(0);

        // Build page break style
        if (true === $options['page_break']) {
            $pagebreak_style = $xml->createElement('style:style');
            $pagebreak_style->setAttribute('style:name', 'pagebreak');
            $pagebreak_style->setAttribute('style:family', 'paragraph');
            $pagebreak_style_properties = $xml->createElement('style:paragraph-properties');
            $pagebreak_style_properties->setAttribute('fo:break-before', 'page');
            $pagebreak_style->appendChild($pagebreak_style_properties);
            $xml->getElementsByTagName('automatic-styles')->item(0)->appendChild($pagebreak_style);
            $this->addFromString('content.xml', $xml->saveXML());
        }

        // Keep current odt reference for the rendering process
        $odt = $this;

        // Build all document pages
        foreach ($pages as $index => $page) {
            // Duplicate and append new page using page break element if index > 0
            if ($index > 0 && true === $options['page_break']) {
                $xml->loadXML($odt->getEntryContents('content.xml'));
                $pagebreak = $xml->createElement('text:p');
                $pagebreak->setAttribute('text:style-name', 'pagebreak');
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

    /**
     * Replace a tag with a value in the ODT content.
     *
     * @param string $tag   tag name to replace
     * @param string $value value to replace the tag with
     */
    public function replace(string $tag, string $value) : void
    {
        $content = $this->getEntryContents('content.xml');

        // Suppress XML errors for invalid HTML because we are going to manipulate some unknown namespaces
        // This is necessary because the value content may contain HTML tags that are not valid in ODT XML
        // and we want to avoid warnings or errors when loading the XML.
        libxml_use_internal_errors(true);

        // Load the content as a DOMDocument
        $dom = new \DOMDocument();
        $dom->loadXML($content);

        // Find the node containing the tag
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query("//*[text() = '$tag']");
        if (0 === $nodes->length) {
            throw new \RuntimeException("Tag '$tag' not found in content.xml");
        }

        // Iterate over all nodes that match the tag
        // and replace them with the converted xml content
        foreach ($nodes as $node) {
            // Create a fragment from the input XML value
            $fragment = $dom->createDocumentFragment();
            $fragment->appendXML($value);
            // Replace the entire node (parent tag) with the fragment
            $node->parentNode->replaceChild($fragment, $node);
        }

        // Add the modified content back to the ODT
        $this->addFromString('content.xml', $dom->saveXML());
    }

    /**
     * Add an image to the ODT manifest and return its path.
     */
    public function addImageToManifest(\Intervention\Image\Image $image) : string
    {
        // Get mime type
        $mime = $image->mime();

        // Generate unique filename for the image
        $id = 'IMG'.md5($image->encoded);

        // Add image file to the odt package
        $image_path = 'Pictures/'.$id;
        $this->addFromString($image_path, $image);

        // Update manifest
        $xml = new \DOMDocument();
        $xml->loadXML($this->getEntryContents('META-INF/manifest.xml'));
        $new_entry = $xml->createElement('manifest:file-entry');
        $new_entry->setAttribute('manifest:media-type', $mime);
        $new_entry->setAttribute('manifest:full-path', $image_path);
        $xml->getElementsByTagName('manifest')->item(0)->appendChild($new_entry);
        $this->addFromString('META-INF/manifest.xml', $xml->saveXML());

        // Return the image path
        return $image_path;
    }
}
