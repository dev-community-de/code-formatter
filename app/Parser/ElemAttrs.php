<?php

declare(strict_types=1);
namespace DevCommunityDE\CodeFormatter\Parser;

/**
 * this class represents element attributes in a
 * raw (match) and a tokenized (pairs) form.
 *
 * @internal
 */
final class ElemAttrs
{
    /** @var string */
    private $match;

    /** @var array */
    private $pairs;

    /**
     * constructor.
     *
     * @param string               $match
     * @param array<string,string> $pairs
     */
    public function __construct(string $match, array $pairs)
    {
        $this->match = $match;
        $this->pairs = $pairs;
    }

    /**
     * returns a value.
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getValue(string $name): ?string
    {
        return $this->pairs[$name] ?? null;
    }

    /**
     * returns the matched attribute string.
     *
     * @return string
     */
    public function getMatch(): string
    {
        return $this->match;
    }

    /**
     * checks if some attributes are set.
     *
     * @return bool
     */
    public function hasMatch(): bool
    {
        return !empty($this->match);
    }
}
