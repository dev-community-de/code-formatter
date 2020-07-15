<?php

declare(strict_types=1);
namespace DevCommunityDE\CodeFormatter\Parser;

/**
 * this class represents a bbcode.
 */
final class ElemNode extends Node
{
    /** @var string */
    private $name;

    /** @var ElemAttrs */
    private $attrs;

    /**
     * constructs a new code-token.
     *
     * @param string        $name
     * @param ElemAttrs     $attrs
     * @param Node[]|string $body
     */
    public function __construct(string $name, ElemAttrs $attrs, $body)
    {
        parent::__construct(Node::KIND_ELEM, $body);
        $this->name = $name;
        $this->attrs = $attrs;
    }

    /**
     * checks if this node represents a [CODE] bbcode.
     *
     * @override
     *
     * @return bool
     */
    public function isCode(): bool
    {
        return !strcasecmp($this->name, 'code');
    }

    /**
     * returns the element name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * returns a single node attribute.
     *
     * @override
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getAttr(string $name): ?string
    {
        return $this->attrs->getValue($name);
    }

    /**
     * returns the node attributes.
     *
     * @override
     *
     * @return string
     */
    public function getAttrMatch(): string
    {
        return $this->attrs->getMatch();
    }
}
