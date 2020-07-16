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

    /** @var NodeList|string */
    private $body;

    /**
     * constructs a new code-token.
     *
     * @param string          $name
     * @param ElemAttrs       $attrs
     * @param NodeList|string $body
     */
    public function __construct(string $name, ElemAttrs $attrs, $body)
    {
        \assert(\is_string($body) || $body instanceof NodeList);
        parent::__construct(Node::KIND_ELEM);
        $this->name = $name;
        $this->attrs = $attrs;
        $this->body = $body;
    }

    /**
     * checks if this node represents a [CODE] bbcode.
     *
     * @return bool
     */
    public function isCode(): bool
    {
        return 0 === strcasecmp($this->name, 'code');
    }

    /**
     * checks if this node represents a [CODE=rich] bbcode.
     *
     * @return bool
     */
    public function isRich(): bool
    {
        if (0 !== strcasecmp($this->name, 'code')) {
            return false;
        }

        $langAttr = $this->getLang();

        if (empty($langAttr)) {
            /* defaults to "text" */
            return false;
        }

        return 0 === strcasecmp($langAttr, 'rich');
    }

    /**
     * utility method to retrieve a lang-attribute.
     *
     * @return string|null
     */
    public function getLang(): ?string
    {
        $langAttr = $this->getAttr('lang');

        if (null === $langAttr && $this->isCode()) {
            $langAttr = $this->getAttr('@value');
        }

        return $langAttr;
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
     * @return string
     */
    public function getAttrMatch(): string
    {
        return $this->attrs->getMatch();
    }

    /**
     * returns the node-body.
     *
     * @return NodeList|string
     */
    public function getBody()
    {
        return $this->body;
    }
}
