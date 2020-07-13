<?php

declare(strict_types=1);

namespace DevCommunityDE\CodeFormatter\Parser;

use DevCommunityDE\CodeFormatter\Exceptions\Exception;

/**
 * this parser uses pcre (regular expressions)
 * to parse incoming code.
 */
final class Parser
{
    /** pattern used to tokenize bbcode tags */
    private const RE_TAGS = '/\G\[(code|plain)(=\w+|(?:\s+\w+=(?:"[^"]*"|\S+))*)\]/i';

    /** pattern used to tokenize attributes */
    private const RE_ATTR = '/\G\s*(\w+)=("[^"]*"|\S+)/';

    /**
     * @param string $filePath
     *
     * @return iterable<Node>
     */
    public function parseFile(string $filePath): iterable
    {
        $textData = file_get_contents($filePath);

        if (false === $textData) {
            throw new Exception('unable to parse file: ' . $filePath);
        }

        return $this->parseText($textData);
    }

    /**
     * @param string $textData
     *
     * @return iterable<Node>
     */
    public function parseText(string $textData): iterable
    {
        $length = \strlen($textData);
        $offset = 0;
        $buffer = '';
        while ($offset < $length) {
            if (!preg_match(self::RE_TAGS, $textData, $m, 0, $offset)) {
                // not a bbcode at the current offset
                // we know for sure that a leading '[' did not start
                // a bbcode, so we can safely assume that the span
                // has to be at least 1
                $span = max(1, strcspn($textData, '[', $offset));
                $body = substr($textData, $offset, $span) ?: '';
                $offset += $span;
                $buffer .= $body;
                continue;
            }

            if ('' !== $buffer) {
                // yield a single text-node using the current buffer
                yield new TextNode($buffer);
                $buffer = '';
            }

            // read from $textData until we see a closing tag
            $name = $m[1];
            $attrs = $this->parseAttrs($m[2]);
            $start = $offset + \strlen($m[0]);
            $needle = "[/{$name}]";
            $position = stripos($textData, $needle, $start);

            if (false === $position) {
                // edge case:
                // no closing tag found... treat the rest of the input
                // as bbcode-body and stop the parsing entirely
                $body = substr($textData, $start) ?: '';
                yield new ElemNode($name, $attrs, $body);
                return;
            }

            $body = substr($textData, $start, ($position - $start)) ?: '';
            $offset = $position + \strlen($needle);
            yield new ElemNode($name, $attrs, $body);
        }

        // check if we still have some content in the buffer
        if ('' !== $buffer) {
            // yield the remaining buffer as text-node
            yield new TextNode($buffer);
        }
    }

    /**
     * parses an attribute string from a bbcode.
     *
     * @param string $attrs
     *
     * @return ElemAttrs
     */
    private function parseAttrs(string $attrs): ElemAttrs
    {
        $pairs = [];
        $match = $attrs;

        if (empty($attrs = trim($attrs))) {
            return new ElemAttrs($match, $pairs);
        }

        if ('=' === substr($attrs, 0, 1)) {
            // [bbcode=value] notation
            $pairs['@value'] = substr($attrs, 1);
            return new ElemAttrs($match, $pairs);
        }

        $offset = 0;
        $length = \strlen($attrs);
        while ($offset < $length) {
            if (!preg_match(self::RE_ATTR, $attrs, $m, 0, $offset)) {
                // we could throw here, but the text-input is in most
                // cases provided by users. so we just discard it
                break;
            }
            $pairs[$m[1]] = $this->parseAttr($m[2]);
            $offset += \strlen($m[0]);
        }

        return new ElemAttrs($match, $pairs);
    }

    /**
     * parses an attribute (value).
     *
     * @param string $value
     *
     * @return string
     */
    private function parseAttr(string $value): string
    {
        switch (substr($value, 0, 1)) {
            case '"':
            case "'":
                return substr($value, 1, -1) ?: '';
            default:
                return $value;
        }
    }

    /**
     * exports node.
     *
     * @param Node        $node
     * @param string|null $body
     *
     * @return string
     */
    public function exportNode(Node $node, ?string $body): string
    {
        if ($node instanceof TextNode) {
            return $body ?: $node->getBody();
        }

        \assert($node instanceof ElemNode);
        $bbcode = $node->getName();
        $buffer = "[{$bbcode}";
        $buffer .= $node->getAttrMatch();
        $buffer .= ']';
        $buffer .= $body ?: $node->getBody();
        return $buffer . "[/{$bbcode}]";
    }
}
