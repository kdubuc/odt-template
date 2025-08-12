<?php

require __DIR__.'/../vendor/autoload.php';

// Ensure the file path is correct
const filePath = __DIR__.'/../tests/template.odt';
if (!file_exists(filePath)) {
    throw new RuntimeException('File not found: '.filePath);
}

// Load the ODT file
/** @var Kdubuc\Odt\Odt $odt */
$odt   = (new Kdubuc\Odt\Odt())->openFile(filePath);
$pages = require __DIR__.'/fixtures.php';
$odt   = $odt->render($pages, [], []);

// Get the content of the ODT file
$content = $odt->getEntryContents('content.xml');
if (false === $content) {
    throw new RuntimeException('Failed to read content.xml from ODT file.');
}

// Output the content
header('Content-Type: text/xml; charset=utf-8');
echo $content;
