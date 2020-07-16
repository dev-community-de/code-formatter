<?php

declare(strict_types=1);
namespace DevCommunityDE\CodeFormatter\Parser;

use IteratorAggregate;
use Traversable;

final class NodeList implements IteratorAggregate
{
    /** @var Node[] */
    private $nodes;

    /**
     * constructs a new node-list.
     *
     * @param Node[] $nodes
     */
    public function __construct(array $nodes = [])
    {
        $this->nodes = $nodes;
    }

    /**
     * appends a node.
     *
     * @param Node|string $node
     *
     * @return void
     */
    public function append($node)
    {
        if (\is_string($node)) {
            $this->nodes[] = new TextNode($node);
            return;
        }

        \assert($node instanceof Node);
        $this->nodes[] = $node;
    }

    /**
     * returns the node-list as array.
     * this method is mainly used for testing.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->nodes;
    }

    /**
     * returns the node-list as traversable.
     *
     * @return Traversable<Node>
     */
    public function getIterator(): Traversable
    {
        yield from $this->nodes;
    }
}
