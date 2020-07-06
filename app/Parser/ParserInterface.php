<?php

declare(strict_types=1);

/*
 * This file is part of the dev-community code formatter
 * (c) The dev-community.de authors
 */

namespace DevCommunityDE\CodeFormatter\Parser;

interface ParserInterface
{
    /**
     * parses the given file and returns a node-list
     * based on the file contents.
     *
     * @param string $file
     *
     * @return ParseNode[]
     */
    public function parse(string $file): array;

    /**
     * exports a parsed node as string.
     *
     * @param ParseNode   $node
     * @param string|null $body
     *
     * @return string
     */
    public function export(ParseNode $node, ?string $body): string;
}
