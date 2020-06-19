<?php

use Kdubuc\Odt\Odt;
use PHPUnit\Framework\TestCase;

class DocumentTest extends TestCase
{
    public function testDocumentRendering() : void
    {
        $pages = [
            // Page 1
            [
                'title'         => 'DOCUMENT TEST PAGE 1',
                'value_a'       => '&é"(§è!çà',
                'value_b'       => '-)àç!è§(',
                'multiple_data' => [
                    ['name' => 'foo', 'test_data' => false],
                    ['name' => 'bar', 'test_data' => true],
                ],
                'url'       => 'https://kevindubuc.fr',
                'image_url' => __DIR__.'/placeholder_150.png',
                'test_date' => '1988-08-14',
            ],

            // Page 2
            [
                'title'         => 'DOCUMENT TEST PAGE 2',
                'value_a'       => '-)àç!è§(',
                'value_b'       => '&é"(§è!çà',
                'multiple_data' => [
                    ['name' => 'baz', 'test_data' => true],
                    ['name' => 'foo', 'test_data' => false],
                ],
                'url'       => 'https://google.fr',
                'image_url' => __DIR__.'/placeholder_300.png',
                'test_date' => '2020-01-01',
            ],
        ];

        $odt = (new Odt())->openFile(__DIR__.'/template.odt')->render($pages, [], []);

        $odt_expected = (new Odt())->openFile(__DIR__.'/expected.odt');

        $this->assertEquals($odt_expected->getEntryContents('content.xml'), $odt->getEntryContents('content.xml'));
    }
}
