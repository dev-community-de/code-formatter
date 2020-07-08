<?php

declare(strict_types=1);

/*
 * This file is part of the DevCom Async Code Formatter
 * (c) The dev-community.de authors
 */

namespace DevCommunityDE\CodeFormatter\Parser;

interface Parser
{
    /**
     * parses a file.
     *
     * @param string $filePath
     *
     * @return iterable<Token>
     */
    public function parseFile(string $filePath): iterable;

    /**
     * parses a string (text).
     *
     * @param string $textData
     *
     * @return iterable<Token>
     */
    public function parseText(string $textData): iterable;

    /**
     * exports a parsed token to its string-form.
     *
     * @param Token       $token
     * @param string|null $body
     *
     * @return string
     */
    public function exportToken(Token $token, ?string $body): string;
}
