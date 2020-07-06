<?php

declare(strict_types=1);

/*
 * This file is part of the dev-community code formatter
 * (c) The dev-community.de authors
 */

namespace DevCommunityDE\CodeFormatter;

use DevCommunityDE\CodeFormatter\CodeFormatter\CodeFormatter;
use DevCommunityDE\CodeFormatter\Parser\ParseNode;
use DevCommunityDE\CodeFormatter\Parser\ParserInterface;

/**
 * Class CodeFormatterApp.
 */
final class CodeFormatterApp
{
    /** @var ParserInterface */
    private $codeParser;

    /**
     * constructor.
     *
     * @param ParserInterface $codeParser
     */
    public function __construct(ParserInterface $codeParser)
    {
        $this->codeParser = $codeParser;
    }

    /**
     * @return void
     */
    public function run()
    {
        $nodes = $this->parseFile('php://input');

        foreach ($nodes as $node) {
            echo $this->formatNode($node);
        }
    }

    /**
     * parses the given file.
     *
     * @param string $file
     *
     * @return array
     */
    private function parseFile(string $file): array
    {
        return $this->codeParser->parse($file);
    }

    /**
     * formats a node.
     *
     * @param ParseNode $node
     *
     * @return string
     */
    private function formatNode(ParseNode $node): string
    {
        if ($node->isText()) {
            return $node->getBody();
        }

        \assert($node->isCode());
        $formatter = CodeFormatter::create($node->getAttribute('lang'));

        if (null === $formatter) {
            // no formatter found, return code as-is
            return $this->exportNode($node, null);
        }

        $result = $this->formatCode($formatter, $node);
        return $this->exportNode($node, $result);
    }

    /**
     * exports a node-node as string.
     *
     * @param ParseNode   $node
     * @param string|null $body
     *
     * @return string
     */
    private function exportNode(ParseNode $node, ?string $body): string
    {
        return $this->codeParser->export($node, $body);
    }

    /**
     * formats the given node.
     *
     * @param CodeFormatter $formatter
     * @param ParseNode     $node
     *
     * @return string
     */
    private function formatCode(CodeFormatter $formatter, ParseNode $node): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'code-formatter');
        rename($tempFile, $tempFile .= '.' . $node->getAttribute('lang'));
        chmod($tempFile, 0666); // other scripts need write-access

        file_put_contents($tempFile, $node->getBody());
        $formatter->exec($tempFile);
        $result = file_get_contents($tempFile);

        unlink($tempFile); // TODO necessary?

        return $result;
    }
}
