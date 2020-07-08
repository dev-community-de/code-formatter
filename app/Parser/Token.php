<?php

declare(strict_types=1);

namespace DevCommunityDE\CodeFormatter\Parser;

final class Token
{
    /** text token */
    public const T_TEXT = 1;

    /** code token */
    public const T_CODE = 2;

    /** @var int */
    private $kind;

    /** @var string */
    private $body;

    /** @var array<string,string> */
    private $attrs = [];

    /**
     * constructor.
     *
     * @param int                       $kind
     * @param string                    $body
     * @param array<string,string>|null $attrs
     */
    public function __construct(int $kind, string $body, ?array $attrs)
    {
        $this->kind = $kind;
        $this->body = $body;

        if (null !== $attrs) {
            $this->attrs = $attrs;
        }
    }

    /**
     * returns true if this token represents a text-token.
     *
     * @return bool
     */
    public function isText(): bool
    {
        return self::T_TEXT === $this->kind;
    }

    /**
     * returns true if this token represents a code-token.
     *
     * @return bool
     */
    public function isCode(): bool
    {
        return self::T_CODE === $this->kind;
    }

    /**
     * returns the token body.
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * returns a single attribute.
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
     * returns a COW reference to this tokens attributes.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attrs;
    }
}
