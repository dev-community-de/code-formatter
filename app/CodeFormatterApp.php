<?php

namespace DevCommunityDE\CodeFormatter;

use DevCommunityDE\CodeFormatter\CodeFormatter\CodeFormatter;
use DevCommunityDE\CodeFormatter\Exceptions\Exception;
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

        if ($node->isRich()) {
            // iterate over all child-nodes in this case
            $buffer = '';
            foreach ($node->getBody() as $subNode) {
                $buffer .= $this->formatNode($subNode);
            }
            return $this->exportNode($node, $buffer);
        }

        $language = $node->getLang() ?: 'text';
        $formatter = CodeFormatter::create($language);

        if (null === $formatter) {
            // no formatter found, return node as-is
            return $this->exportNode($node, null);
        }

        $nodeBody = $node->getBody();
        \assert(\is_string($nodeBody));

        try {
            $result = $formatter->exec($nodeBody);
            return $this->exportNode($node, $result);
        } catch (Exception $e) {
            // null => use original code-body
            return $this->exportNode($node, null);
        }
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
