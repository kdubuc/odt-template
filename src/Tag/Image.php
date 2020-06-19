<?php

namespace Kdubuc\Odt\Tag;

use DOMDocument;
use Kdubuc\Odt\Odt;
use Adbar\Dot as ArrayDot;
use Intervention\Image\ImageManager as Manager;
use Intervention\Image\Exception\NotReadableException as ImageNotReadableException;

final class Image extends Tag
{
    public const PIXEL_TO_CM = 0.0264583333;

    /*
     * Regex to isolate tag inside Odt content.
     */
    public function getRegex() : string
    {
        return "/{image:(?'key'[\w.]+)}/";
    }

    /*
     * Render process using regex and tag informations.
     */
    public function render(Odt $odt, ArrayDot $data, array $tag_infos) : Odt
    {
        // Get the image url
        $url = $data->get($tag_infos['key']);

        // We try to reach the image to add it to the odt doc
        try {
            // Get image from the url given (local, remote ...)
            $image = (new Manager(['driver' => 'imagick']))->make($url);

            // Get mime type
            $mime = $image->mime();

            // Get image size
            $width  = $image->width() * self::PIXEL_TO_CM;
            $height = $image->height() * self::PIXEL_TO_CM;

            // Generate image
            $image = $image->encode();

            // Add image file to the odt package
            $image_path = 'Pictures/IMG'.hash('md5', hash_file('md5', $image->basePath()));
            $odt->addFromString($image_path, $image);

            // Update manifest
            $xml = new DOMDocument();
            $xml->loadXML($odt->getEntryContents('META-INF/manifest.xml'));
            $new_entry = $xml->createElement('manifest:file-entry');
            $new_entry->setAttribute('manifest:media-type', $mime);
            $new_entry->setAttribute('manifest:full-path', $image_path);
            $xml->getElementsByTagName('manifest')->item(0)->appendChild($new_entry);
            $odt->addFromString('META-INF/manifest.xml', $xml->saveXML());

            // Update content.xml
            $tag        = preg_quote($tag_infos[0], '/');
            $content    = $odt->getEntryContents('content.xml');
            $xml        = new DOMDocument();
            $draw_frame = $xml->createElement('draw:frame'); // Add frame
            $draw_frame->setAttribute('text:anchor', 'aschar');
            $draw_frame->setAttribute('svg:width', "{$width}cm");
            $draw_frame->setAttribute('svg:height', "{$height}cm");
            $draw_image = $xml->createElement('draw:image'); // Add image
            $draw_image->setAttribute('xlink:href', $image_path);
            $draw_frame->appendChild($draw_image); // Update tag tree
            $xml->appendChild($draw_frame);
            $odt->addFromString('content.xml', preg_replace("/$tag/", $xml->saveHTML(), $content));
        } catch (ImageNotReadableException $e) {
            // $content = $odt->getEntryContents('content.xml');
            // $odt->addFromString('content.xml', preg_replace("/$tag/", "Image not readable", $content));
        }

        return $odt;
    }
}
