<?php

namespace DevCommunityDE\CodeFormatter;

use DevCommunityDE\CodeFormatter\CodeFormatter\CodeFormatter;
use DevCommunityDE\CodeFormatter\Parser\ElemNode;
use DevCommunityDE\CodeFormatter\Parser\Node;
use DevCommunityDE\CodeFormatter\Parser\Parser;
use DevCommunityDE\CodeFormatter\Parser\TextNode;

/**
 * Class CodeFormatterApp.
 */
class CodeFormatterApp
{
    /** @var Parser */
    private $parser;

    /**
     * constructs the app.
     *
     * @param Parser|null $parser
     */
    public function __construct(Parser $parser = null)
    {
        $this->parser = $parser ?? new Parser();
    }

    /**
     * runs the application.
     *
     * @return void
     */
    public function run()
    {
        $nodes = $this->parseInput();

        foreach ($nodes as $node) {
            echo $this->formatNode($node);
        }
    }

    /**
     * parses the code from stdin.
     *
     * @return iterable<Node>
     */
    private function parseInput(): iterable
    {
        return $this->parser->parseFile('php://input');
    }

    /**
     * formats a node based on its type and language.
     *
     * @param Node $node
     *
     * @return string
     */
    private function formatNode(Node $node): string
    {
        if ($node instanceof TextNode) {
            return $this->exportNode($node, null);
        }

        \assert($node instanceof ElemNode);

        if (!$node->isCode()) {
            // export node as-is (PLAIN bbcode)
            return $this->exportNode($node, null);
        }

        $nodeBody = $node->getBody();

        if (\is_array($nodeBody)) {
            $buffer = '';
            foreach ($nodeBody as $subNode) {
                $buffer .= $this->formatNode($subNode);
            }
            return $this->exportNode($node, $buffer);
        }

        $language = $this->detectLang($node);
        $formatter = CodeFormatter::create($language);

        if (null === $formatter) {
            // no formatter found, return node as is
            return $this->exportNode($node, null);
        }

        \assert(\is_string($nodeBody));
        $result = $formatter->exec($nodeBody);
        return $this->exportNode($node, $result);
    }

    /**
     * detect a code-language from the given node.
     *
     * @param ElemNode $node
     *
     * @return string
     */
    private function detectLang(ElemNode $node): string
    {
        $value = $node->getAttr('lang');

        if (empty($value)) {
            // check if a immediate value is set
            // (this is the [code=lang] notation)
            $value = $node->getAttr('@value');
        }

        return $value ?: 'text';
    }

    /**
     * exports a node.
     *
     * @param Node        $node
     * @param string|null $body
     *
     * @return string
     */
    private function exportNode(Node $node, ?string $body): string
    {
        return $this->parser->exportNode($node, $body);
    }
}
