<?php

declare(strict_types=1);
namespace DevCommunityDE\CodeFormatter\Parser;

/**
 * this token represents a bbcode.
 */
final class CodeToken extends Token
{
    /** @var string */
    private $name;

    /** @var TokenAttrs */
    private $attrs;

    /**
     * constructs a new code-token.
     *
     * @param string     $name
     * @param TokenAttrs $attrs
     * @param string     $body
     */
    public function __construct(string $name, TokenAttrs $attrs, string $body)
    {
        parent::__construct(
            Token::T_CODE,
            $body
        );

        $this->name = $name;
        $this->attrs = $attrs;
    }

    /**
     * returns the token name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * checks if the token name equals the given name.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasName(string $name): bool
    {
        return 0 === strcasecmp($this->name, $name);
    }

    /**
     * returns the token attributes.
     *
     * @return TokenAttrs
     */
    public function getAttrs(): TokenAttrs
    {
        return $this->attrs;
    }

    /**
     * returns a single attribute.
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getAttr(string $name): ?string
    {
        return $this->attrs->getValue($name);
    }
}
