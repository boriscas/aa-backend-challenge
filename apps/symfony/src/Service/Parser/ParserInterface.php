<?php

namespace App\Service\Parser;

/**
 * This interface is used to provide a contract if more ways to parse a dom content are implemented in the future
 */
interface ParserInterface
{
    public function countFilteredElements(\DOMDocument $document, string $elementTagName): int;

    public function getFirstElementValue(\DOMDocument $document, string $elementTagName): ?string;

    public function getAnyElementAttributeValue(
        \DOMDocument $document,
        string $elementTagName,
        string $elementAttributeName
    ): ?string;

    public function getDocumentWordCount(\DOMDocument $document): int;
}