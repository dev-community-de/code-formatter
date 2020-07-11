<?php

namespace DevCommunityDE\CodeFormatter;

use DevCommunityDE\CodeFormatter\CodeFormatter\CodeFormatter;
use DevCommunityDE\CodeFormatter\Parser\Parser;
use DevCommunityDE\CodeFormatter\Parser\PregParser;
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
        $this->parser = $parser ?? new PregParser();
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
        if ($token->isText()) {
            return $token->getBody();
        }

        $language = $token->getAttribute('lang');
        \assert(null !== $language);

        $formatter = CodeFormatter::create($language);
        if (null === $formatter) {
            // no formatter found, return token as is
            return $this->exportToken($token, null);
        }

        $result = $formatter->exec($token->getBody());
        return $this->exportToken($token, $result);
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
