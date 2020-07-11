<?php

declare(strict_types=1);
namespace DevCommunityDE\CodeFormatter\Parser;

/**
 * this token represents a text-token.
 */
final class TextToken extends Token
{
    /**
     * constructs a new text-token.
     *
     * @param string $body
     */
    public function __construct(string $body)
    {
        parent::__construct(
            Token::T_TEXT,
            $body
        );
    }
}
