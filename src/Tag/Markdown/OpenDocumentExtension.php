<?php

namespace Kdubuc\Odt\Tag\Markdown;

use League\CommonMark\Node\Node;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;

class OpenDocumentExtension implements NodeRendererInterface, ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment) : void
    {
        $environment->addRenderer(Paragraph::class, $this);
        $environment->addRenderer(Heading::class, $this);
        $environment->addRenderer(ListBlock::class, $this);
        $environment->addRenderer(ListItem::class, $this);
        $environment->addRenderer(Text::class, $this);
        $environment->addRenderer(Document::class, $this);
        $environment->addRenderer(Strong::class, $this);
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

        return $childRenderer->renderNodes($node->children());
    }
}
