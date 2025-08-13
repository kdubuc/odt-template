<?php

namespace Kdubuc\Odt\Tag;

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
    protected function getRegex() : string
    {
        return "/{image:(?'key'[\w.]+)}/";
    }

    /*
     * Render process : Within odt, edit tag with data bag.
     */
    protected function render(Odt $odt, ArrayDot $data, array $tag_infos) : Odt
    {
        // Get the image url
        $url = $data->get($tag_infos['key']);

        // We try to reach the image to add it to the odt doc
        try {
            // Get image from the url given (local, remote ...)
            $image = (new Manager(['driver' => 'imagick']))->make($url);

            // Get image size
            $width  = $image->width() * self::PIXEL_TO_CM;
            $height = $image->height() * self::PIXEL_TO_CM;

            // Generate image
            $image = $image->encode();

            // Add image to manifest
            $image_path = $odt->addImageToManifest($image);

            // Update content.xml
            $tag        = preg_quote($tag_infos[0], '/');
            $content    = $odt->getEntryContents('content.xml');
            $xml        = new \DOMDocument();
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
