<?php

declare(strict_types=1);

namespace DevCommunityDE\CodeFormatter\Parser;

abstract class Node
{
    /** text node */
    public const KIND_TEXT = 1;

    /** element node (bbcode) */
    public const KIND_ELEM = 2;

    /** @var int */
    private $kind;

    /**
     * constructs a new token.
     *
     * @param int $kind
     */
    public function __construct(int $kind)
    {
        $this->kind = $kind;
    }

    /**
     * returns the node kind.
     *
     * @return int
     */
    public function getKind(): int
    {
        return $this->kind;
    }
}
