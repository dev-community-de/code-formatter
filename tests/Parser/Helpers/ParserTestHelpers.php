<?php

declare(strict_types=1);
namespace DevCommunityDE\CodeFormatter\Tests\Parser\Helpers;

use DevCommunityDE\CodeFormatter\Parser\Parser;

trait ParserTestHelpers
{
    /**
     * @param string $input
     */
    private function parseFileToArray(string $input)
    {
        $parser = new Parser();
        return iterator_to_array($parser->parseFile($input));
    }

    /**
     * @param string $input
     */
    private function parseTextToArray(string $input)
    {
        $parser = new Parser();
        return iterator_to_array($parser->parseText($input));
    }
}
