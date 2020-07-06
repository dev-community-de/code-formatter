<?php

namespace DevCommunityDE\CodeFormatter\Parser;

final class ParseNode
{
    // regular text
    public const KIND_TEXT = 1;

    // text wrapped with [code][/code] including the code-tags
    public const KIND_CODE = 2;

    /** @var int */
    private $kind;

    /** @var string */
    private $body;

    /** @var array<string,string> */
    private $attrs = [];

    /**
     * constructs a new parse-node.
     *
     * @param int                  $kind
     * @param string               $body
     * @param array<string,string> $attrs
     */
    public function __construct(int $kind, string $body, array $attrs = [])
    {
        $this->kind = $kind;
        $this->body = $body;
        $this->attrs = $attrs;
    }

    /**
     * checks if this node represents a text-node.
     *
     * @return bool
     */
    public function isText(): bool
    {
        return self::KIND_TEXT === $this->kind;
    }

    /**
     * checks if this node represents a code-node.
     *
     * @return bool
     */
    public function isCode(): bool
    {
        return self::KIND_CODE === $this->kind;
    }

    /**
     * returns the node-kind (see constants).
     *
     * @return int
     */
    public function getKind(): int
    {
        return $this->kind;
    }

    /**
     * returns the node text body.
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * checks if this node has a specific attribute.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasAttribute(string $name): bool
    {
        return isset($this->attrs[$name]);
    }

    /**
     * returns a specific attribute.
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getAttribute(string $name): ?string
    {
        return $this->attrs[$name] ?? null;
    }

    /**
     * returns a COW reference to all attributes.
     *
     * @return array<string,string>
     */
    public function getAttributes(): array
    {
        return $this->attrs;
    }
}
