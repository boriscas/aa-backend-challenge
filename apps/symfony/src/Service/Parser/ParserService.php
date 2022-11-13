<?php

namespace App\Service\Parser;

use App\Model\Dto\ParserParameter;
use App\Model\Exception\AppElementMissingDiscriminatorAttribute;
use Psr\Log\LoggerInterface;

class ParserService implements ParserInterface
{
    public const UNIQUE_ELEMENT_ATTRIBUTE_DISCRIMINATOR = [
        'a' => 'href',
        'img' => 'src',
    ];

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function getAnyElementAttributeValue(
        \DOMDocument $document,
        string $elementTagName,
        string $elementAttributeName,
        ?string $regex = null
    ): ?string {
        $elements = $document->getElementsByTagName($elementTagName);
        $elementSelected = $elements->item(rand(0, $elements->count() - 1));

        if (null === $elementSelected) {
            return null;
        }

        $elementValue = $this->getAttributeValue($elementSelected, $elementAttributeName);

        if (null !== $regex && preg_match('#' . $regex . '#', $elementValue) !== 1) {
            return null;
        }

        return empty($elementValue) ? null : $elementValue;
    }

    public function getDocumentWordCount(
        \DOMDocument $document
    ): int {
        // Easier with an XPath query
        $xpath = new \DOMXPath($document);
        $nodes = $xpath->query(ParserParameter::QUERY_ALL_TEXT_NODES_XPATH);

        $textNodesContent = '';
        foreach ($nodes as $node) {
            $textNodesContent .= ' ' . trim($node->nodeValue);
        }

        return str_word_count($textNodesContent);
    }

    public function getFirstElementValue(
        \DOMDocument $document,
        string $elementTagName
    ): ?string {
        $elements = $document->getElementsByTagName($elementTagName);
        if ($elements->count() === 0) {
            return null;
        }
        $elementSelected = $elements->item(0);
        return $elementSelected?->textContent;
    }

    /**
     * @throws \Exception
     */
    public function countFilteredElements(
        \DOMDocument $document,
        string $elementTagName,
        ?string $regexFilter = null
    ): int {
        // We NEED to have a mapping value here.
        if (!array_key_exists($elementTagName, self::UNIQUE_ELEMENT_ATTRIBUTE_DISCRIMINATOR)) {
            throw new AppElementMissingDiscriminatorAttribute($elementTagName);
        }

        $elements = $document->getElementsByTagName($elementTagName);

        $elementsCount = $elements->count();
        $this->logger->info(
            sprintf('-- Found %s "%s" HTML elements in DOM content', $elementsCount, $elementTagName)
        );

        return $this->filterElements($elements, $elementTagName, $regexFilter);
    }

    /**
     * Count the uniques elements according to the value of a discriminator attribute specific to each type
     * of element.
     *
     * @param \DOMNodeList $elements
     * @param string $htmlElementName
     * @param string|null $regexValue
     * @return int
     */
    private function filterElements(
        \DOMNodeList $elements,
        string $htmlElementName,
        ?string $regexValue = null
    ): int {
        $uniqueValues = [];
        $duplicatesCount = $regexpNotMatchingCount = $noValueCount = 0;
        $discriminatingAttributeName = self::UNIQUE_ELEMENT_ATTRIBUTE_DISCRIMINATOR[$htmlElementName];

        $it = $elements->getIterator();
        $it->rewind(); // let's just be sure it's at the beginning

        while ($it->valid()) {
            $element = $it->current();
            $elementValue = $this->getAttributeValue($element, $discriminatingAttributeName);

            // No value, no problem
            if (empty($elementValue)) {
                $noValueCount++;
                $it->next();
                continue;
            }

            // Uniqueness filtering
            if (in_array($elementValue, $uniqueValues)) {
                $duplicatesCount++;
                $it->next();
                continue;
            }

            // Regexp filtering (if required)
            if (null !== $regexValue && preg_match('#' . $regexValue . '#', $elementValue) !== 1) {
                $regexpNotMatchingCount++;
                $it->next();
                continue;
            }

            $uniqueValues[] = $elementValue;
            $it->next();
        }

        $this->logger->info(
            sprintf(
                '-- Found %s duplicates for HTML elements "%s" in DOM content',
                $duplicatesCount,
                $htmlElementName
            )
        );
        $this->logger->info(
            sprintf('-- Filtered %s HTML elements from regex in DOM content', $regexpNotMatchingCount)
        );
        $this->logger->info(
            sprintf('-- Filtered %s HTML elements without any value in DOM content', $noValueCount)
        );

        return count($uniqueValues);
    }

    /**
     * @link https://www.php.net/manual/en/class.domnode.php#domnode.props.attributes
     * Therefore, we have to avoid unsecure polymorphic call to getAttribute() and ensure we have a DOMElement which
     * type is not specialized enough for our needs and not another DOM* item with attributes property.
     *
     * @param \DOMNode|null $node
     * @param string $attributeName
     * @return string|null or empty string if no attributeName is found
     */
    private function getAttributeValue(?\DOMNode $node, string $attributeName): ?string
    {
        /** @var \DOMElement $node */
        return $node->nodeType === XML_ELEMENT_NODE ? $node->getAttribute($attributeName) : null;
    }
}