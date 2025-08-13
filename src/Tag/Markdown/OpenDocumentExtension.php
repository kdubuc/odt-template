<?php

namespace Kdubuc\Odt\Tag\Markdown;

use Kdubuc\Odt\Odt;
use League\CommonMark\Node\Node;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline;

class OpenDocumentExtension implements NodeRendererInterface, ExtensionInterface
{
    private Odt $odt;

    public function __construct(
        Odt $odt
    ) {
        $this->odt = $odt;
    }

    public function register(EnvironmentBuilderInterface $environment) : void
    {
        $environment->addRenderer(Paragraph::class, $this);
        $environment->addRenderer(Heading::class, $this);
        $environment->addRenderer(ListBlock::class, $this);
        $environment->addRenderer(ListItem::class, $this);
        $environment->addRenderer(Text::class, $this);
        $environment->addRenderer(Document::class, $this);
        $environment->addRenderer(Strong::class, $this);
        $environment->addRenderer(Image::class, $this);
        $environment->addRenderer(HtmlBlock::class, $this);
        $environment->addRenderer(HtmlInline::class, $this);
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer)
    {
        // Paragraphe
        if ($node instanceof Paragraph) {
            return '<text:p>'.$childRenderer->renderNodes($node->children()).'</text:p>';
        }

        // Titre (heading)
        if ($node instanceof Heading) {
            $level = $node->getLevel();

            return '<text:h text:outline-level="'.$level.'">'.$childRenderer->renderNodes($node->children()).'</text:h>';
        }

        // Liste non-ordonnée
        if ($node instanceof ListBlock) {
            return '<text:list>'.$childRenderer->renderNodes($node->children()).'</text:list>';
        }

        // Item de liste
        if ($node instanceof ListItem) {
            return '<text:list-item>'.$childRenderer->renderNodes($node->children()).'</text:list-item>';
        }

        // Texte brut
        if ($node instanceof Text) {
            return htmlspecialchars($node->getLiteral(), \ENT_XML1 | \ENT_COMPAT, 'UTF-8');
        }

        // Style Gras
        if ($node instanceof Strong) {
            return '<text:span text:style-name="T1">'.$childRenderer->renderNodes($node->children()).'</text:span>';
        }

        // Image
        if ($node instanceof Image) {
            $src    = htmlspecialchars($node->getUrl(), \ENT_XML1 | \ENT_COMPAT, 'UTF-8');
            $alt    = htmlspecialchars($node->getTitle(), \ENT_XML1 | \ENT_COMPAT, 'UTF-8');
            $image  = (new \Intervention\Image\ImageManager(['driver' => 'imagick']))->make($src)->encode();
            $width  = $image->width() * \Kdubuc\Odt\Tag\Image::PIXEL_TO_CM;
            $height = $image->height() * \Kdubuc\Odt\Tag\Image::PIXEL_TO_CM;

            $path = $this->odt->addImageToManifest($image);

            return '<draw:frame text:anchor="aschar" svg:width="'.$width.'cm" svg:height="'.$height.'cm">
                        <draw:image xlink:href="'.$path.'" xlink:title="'.$alt.'"/>
                    </draw:frame>';
        }

        // HTML Image
        if ($node instanceof HtmlInline || $node instanceof HtmlBlock) {
            // Get the HTML content
            $htmlContent = $node->getLiteral();

            // Load HTML content (wrap in body to ensure proper parsing)
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML('<?xml encoding="UTF-8"><body>'.$htmlContent.'</body>', \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();

            // Get the first element
            $xpath    = new \DOMXPath($dom);
            $elements = $xpath->query('//body/*');

            // Get the first element and its tag name
            $element = $elements->item(0);

            // Check if the element is an image
            if ($element && 'img' === mb_strtolower($element->nodeName) && $element instanceof \DOMElement) {
                $src   = $element->getAttribute('src');
                $alt   = $element->getAttribute('alt');
                $title = $element->getAttribute('title');

                // Process image similar to markdown Image node
                if ($src) {
                    $image  = (new \Intervention\Image\ImageManager(['driver' => 'imagick']))->make($src)->encode();
                    $width  = ($element->getAttribute('width') ?? $image->width()) * \Kdubuc\Odt\Tag\Image::PIXEL_TO_CM;
                    $height = ($element->getAttribute('height') ?? $image->height()) * \Kdubuc\Odt\Tag\Image::PIXEL_TO_CM;
                    $path   = $this->odt->addImageToManifest($image);

                    if ($node instanceof HtmlBlock) {
                        // If it's a block, wrap in a <text:p> tag
                        return '<text:p>
                                    <draw:frame text:anchor="aschar" svg:width="'.$width.'cm" svg:height="'.$height.'cm">
                                        <draw:image xlink:href="'.$path.'" xlink:title="'.htmlspecialchars($alt ?: $title, \ENT_XML1 | \ENT_COMPAT, 'UTF-8').'"/>
                                    </draw:frame>
                                </text:p>';
                    }

                    return '<draw:frame text:anchor="aschar" svg:width="'.$width.'cm" svg:height="'.$height.'cm">
                                <draw:image xlink:href="'.$path.'" xlink:title="'.htmlspecialchars($alt ?: $title, \ENT_XML1 | \ENT_COMPAT, 'UTF-8').'"/>
                            </draw:frame>';
                }
            }
        }

        return $childRenderer->renderNodes($node->children());
    }
}
