<?php

namespace DevCommunityDE\CodeFormatter;

use DevCommunityDE\CodeFormatter\CodeFormatter\CodeFormatter;
use DevCommunityDE\CodeFormatter\Parser\CodeToken;
use DevCommunityDE\CodeFormatter\Parser\Parser;
use DevCommunityDE\CodeFormatter\Parser\TextToken;
use DevCommunityDE\CodeFormatter\Parser\Token;

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
        $tokens = $this->parseInput();

        foreach ($tokens as $token) {
            echo $this->formatToken($token);
        }
    }

    /**
     * parses the code from stdin.
     *
     * @return iterable<Token>
     */
    private function parseInput(): iterable
    {
        return $this->parser->parseFile('php://input');
    }

    /**
     * formats a token based on its type and language.
     *
     * @param Token $token
     *
     * @return string
     */
    private function formatToken(Token $token): string
    {
        if ($token instanceof TextToken) {
            return $token->getBody();
        }

        \assert($token instanceof CodeToken);
        if ($token->hasName('PLAIN')) {
            // export plain codes as-is
            return $this->exportToken($token, null);
        }

        $language = $this->detectLang($token);
        $formatter = CodeFormatter::create($language);
        if (null === $formatter) {
            // no formatter found, return token as is
            return $this->exportToken($token, null);
        }

        $result = $formatter->exec($token->getBody());
        return $this->exportToken($token, $result);
    }

    /**
     * detects a [code] language.
     *
     * @param CodeToken $token
     *
     * @return string
     */
    private function detectLang(CodeToken $token): string
    {
        return
            $token->getAttr('lang')   /* lang="..." */ ??
            $token->getAttr('@value') /* =lang */      ??
            'text'                    /* undefined */
        ;
    }

    /**
     * exports a token.
     *
     * @param Token       $token
     * @param string|null $body
     *
     * @return string
     */
    private function exportToken(Token $token, ?string $body): string
    {
        return $this->parser->exportToken($token, $body);
    }
}
