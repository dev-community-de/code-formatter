<?php

declare(strict_types=1);
namespace DevCommunityDE\CodeFormatter\Tests\Parser\Helpers;

use DevCommunityDE\CodeFormatter\Parser\Node;
use DevCommunityDE\CodeFormatter\Parser\Parser;

trait ParserTestHelpers
{
    /**
     * parses a file and returns it as array of nodes.
     *
     * @param string $input
     *
     * @return Node[]
     */
    private function parseFileToArray(string $input): array
    {
        $parser = new Parser();
        return iterator_to_array($parser->parseFile($input));
    }

    /**
     * parses a text and returns it as array of nodes.
     *
     * @param string $input
     *
     * @return Node[]
     */
    private function parseTextToArray(string $input): array
    {
        $parser = new Parser();
        return iterator_to_array($parser->parseText($input));
    }
}
