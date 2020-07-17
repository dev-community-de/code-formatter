<?php

declare(strict_types=1);
namespace DevCommunityDE\CodeFormatter\Parser;

/**
 * used internally as a parse-state.
 *
 * @internal
 */
final class State
{
    /** @var string */
    public string $input;

    /** @var int */
    public int $length;

    /** @var int */
    public int $offset;

    /**
     * constructs a new state.
     *
     * @param string $input
     */
    public function __construct(string $input)
    {
        $this->input = $input;
        $this->length = \strlen($input);
        $this->offset = 0;
    }

    /**
     * checks if this state is still valid.
     *
     * @return bool
     */
    public function valid(): bool
    {
        return $this->offset < $this->length;
    }

    /**
     * marks the state a finished.
     *
     * @return void
     */
    public function finish()
    {
        $this->offset = $this->length;
    }
}
