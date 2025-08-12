<?php

use Kdubuc\Odt\Odt;
use PHPUnit\Framework\TestCase;

class DocumentTest extends TestCase
{
    public function testDocumentRendering() : void
    {
        $pages = require __DIR__.'/fixtures.php';

        $odt = (new Odt())->openFile(__DIR__.'/template.odt')->render($pages, [], []);

        $odt_expected = (new Odt())->openFile(__DIR__.'/expected.odt');

        $this->assertEquals($odt_expected->getEntryContents('content.xml'), $odt->getEntryContents('content.xml'));
    }
}
