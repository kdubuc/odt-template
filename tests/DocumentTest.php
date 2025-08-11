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
                'title' => 'DOCUMENT TEST PAGE 1',
                'value_a' => '&é"(§è!çà',
                'value_b' => '-)àç!è§(',
                'multiple_data' => [
                    ['name' => 'foo', 'test_data' => false],
                    ['name' => 'bar', 'test_data' => true],
                ],
                'url' => 'https://kevindubuc.fr',
                'image_url' => __DIR__.'/placeholder_150.png',
                'test_date' => '1988-08-14',
                'markdown_content' => '# Hello World!',
                'test_table' => [
                    ['foo' => 'foo1', 'bar' => 'bar1'],
                    ['foo' => 'foo2', 'bar' => 'bar2'],
                ],
            ],

            // Page 2
            [
                'title' => 'DOCUMENT TEST PAGE 2',
                'value_a' => '-)àç!è§(',
                'value_b' => '&é"(§è!çà',
                'multiple_data' => [
                    ['name' => 'baz', 'test_data' => true],
                    ['name' => 'foo', 'test_data' => false],
                ],
                'url' => 'https://google.fr',
                'image_url' => __DIR__.'/placeholder_300.png',
                'test_date' => '2020-01-01',
                'markdown_content' => trim(<<<MD
                    #### CLASSEMENT
                    L'établissement est classé en type R de 3ème catégorie.
                    Avec une activité de Jardins d'enfant
                    L'effectif total est de 70 personnes: 50 au titre du public et 20 au titre du personnel.

                    #### IMPLANTATION
                    - Le bâtiment se compose de 2 niveaux en superstructure
                    - Le bâtiment est de plain-pied.
                    - Le plancher bas du dernier niveau accessible au public est =>8m.
                    Il est desservi par **1** voie(s) engin.

                    #### CONSTRUCTION
                    La stabilité au feu de la structure de l'établissement est de degré 2h.
                    La stabilité au feu des planchers de l'établissement est de degré 3h.

                    #### VENTILATION / DESENFUMAGE
                    L'établissement est désenfumé mécaniquement.
                    L'établissement est désenfumé naturellement.

                    #### ELECTRICITE / ECLAIRAGE
                    Présence de batteries accumulateurs et matériels associés, ayant pour fonction:
                    - Présence de parafoudre
                    MD),
            ],
        ];

        $odt = (new Odt())->openFile(__DIR__.'/template.odt')->render($pages, [], []);

        $odt_expected = (new Odt())->openFile(__DIR__.'/expected.odt');

        $this->assertEquals($odt_expected->getEntryContents('content.xml'), $odt->getEntryContents('content.xml'));
    }
}
