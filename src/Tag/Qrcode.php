<?php

namespace Kdubuc\Odt\Tag;

use DOMDocument;
use Kdubuc\Odt\Odt;
use BaconQrCode\Writer;
use Adbar\Dot as ArrayDot;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;

final class Qrcode extends Tag
{
    /*
     * Regex to isolate tag inside Odt content.
     */
    protected function getRegex() : string
    {
        return "/{qrcode:(?'key'[\w.]+)}/";
    }

    /*
     * Render process : Within odt, edit tag with data bag.
     */
    protected function render(Odt $odt, ArrayDot $data, array $tag_infos) : Odt
    {
        // Url to encode in qrcode
        $url = mb_strtolower($data->get($tag_infos['key']));

        // Generate the qrcode
        $size     = 42;
        $renderer = new ImageRenderer(
            new RendererStyle($size, 0),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrcode = $writer->writeString($url);

        // Add image file to the odt package
        $image_path = 'Pictures/QRC'.hash('md5', $qrcode);
        $odt->addFromString($image_path, $qrcode);

        // Update manifest
        $xml = new DOMDocument();
        $xml->loadXML($odt->getEntryContents('META-INF/manifest.xml'));
        $new_entry = $xml->createElement('manifest:file-entry');
        $new_entry->setAttribute('manifest:media-type', 'image/svg+xml');
        $new_entry->setAttribute('manifest:full-path', $image_path);
        $xml->getElementsByTagName('manifest')->item(0)->appendChild($new_entry);
        $odt->addFromString('META-INF/manifest.xml', $xml->saveXML());

        // Update content.xml
        $tag        = preg_quote($tag_infos[0], '/');
        $content    = $odt->getEntryContents('content.xml');
        $xml        = new DOMDocument();
        $draw_frame = $xml->createElement('draw:frame'); // Add frame
        $draw_frame->setAttribute('text:anchor', 'aschar');
        $draw_frame->setAttribute('svg:width', $size * Image::PIXEL_TO_CM.'cm');
        $draw_frame->setAttribute('svg:height', $size * Image::PIXEL_TO_CM.'cm');
        $draw_image = $xml->createElement('draw:image'); // Add image
        $draw_image->setAttribute('xlink:href', $image_path);
        $draw_frame->appendChild($draw_image); // Update frame tag tree
        $xml->appendChild($draw_frame); // Update draw tag tree
        $odt->addFromString('content.xml', preg_replace("/$tag/", $xml->saveHTML(), $content));

        return $odt;
    }
}
