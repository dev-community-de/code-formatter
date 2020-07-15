<?php

declare(strict_types=1);
namespace DevCommunityDE\CodeFormatter\Parser;

/**
 * this class represents a text-node.
 */
final class TextNode extends Node
{
    /** @var string */
    private $body;

    /**
     * constructs a new text-node.
     *
     * @param string $body
     */
    public function __construct(string $body)
    {
        parent::__construct(Node::KIND_TEXT);
        $this->body = $body;
    }

    /**
     * returns the node-body.
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }
}
