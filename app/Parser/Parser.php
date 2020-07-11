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
        while ($offset < $length) {
            if (!preg_match(self::RE_TAGS, $textData, $m, 0, $offset)) {
                // not a bbcode at the current offset
                $span = strcspn($textData, '[', $offset);
                if (0 === $span) {
                    // we're looking at a '[' but did not match a bbcode
                    // this algorithm optimizes consecutive '[' tokens
                    $start = $offset;
                    while (($offset + 2) < $length) {
                        if ('[' !== $textData[$offset + 2]) {
                            // stop folding '[' tokens, because the next
                            // iteration has to check for a bbcode again
                            break;
                        }
                        ++$offset;
                    }
                    // we add 1 to the span, because we know that
                    // at least the current '[' did not start a bbcode
                    $span = $offset - $start + 1;
                    $offset = $start;
                }
                $body = substr($textData, $offset, $span);
                yield new TextNode($body);
                $offset += $span;
                continue;
            }
            // read from $textData until we see a closing tag
            $name = $m[1];
            $attrs = $this->parseAttrs($m[2]);
            $start = $offset + \strlen($m[0]);
            $close = "[/{$name}]";
            $span = stripos($textData, $close, $start);
            if (false === $span) {
                // edge case:
                // no closing tag found... tread the rest of the input
                // as bbcode-body and stop the iteration
                $body = substr($textData, $start);
                yield new ElemNode($name, $attrs, $body);
                break;
            }
            $body = substr($textData, $start, ($span - $start));
            yield new ElemNode($name, $attrs, $body);
            $offset = $span + \strlen($close);
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

        $attrMap = $node->getAttrs();
        if ($attrMap->hasMatch()) {
            $buffer .= $attrMap->getMatch();
        }

        $buffer .= ']';
        $buffer .= $body ?: $node->getBody();
        return $buffer . "[/{$bbcode}]";
    }
}
