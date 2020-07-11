<?php

declare(strict_types=1);

namespace DevCommunityDE\CodeFormatter\Parser;

abstract class Token
{
    /** text token */
    public const T_TEXT = 1;

    /** bbcode token */
    public const T_CODE = 2;

    /** @var int */
    private $kind;

    /** @var string */
    private $body;

    /**
     * constructs a new token.
     *
     * @param int    $kind
     * @param string $body
     */
    public function __construct(int $kind, string $body)
    {
        $this->kind = $kind;
        $this->body = $body;
    }

    /**
     * @see Token::getKind()
     *
     * @return int
     */
    public function getKind(): int
    {
        return $this->kind;
    }

    /**
     * @see Token::getBody()
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }
}
