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
     * @return iterable<Token>
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
     * @return @return iterable<Token>
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
                    $start = $offset;
                    while (($offset + 1) < $length) {
                        // try again at next offset
                        $span = strcspn($textData, '[', ++$offset);
                        if ($span > 0) {
                            break;
                        }
                    }
                    if (0 === $span) {
                        // edge case:
                        // we could not match anything, this means
                        // the rest of $textData contains only '['
                        $body = substr($textData, $start);
                        yield new TextToken($body);
                        break;
                    }
                    // calculate actual span
                    $span += $offset - $start;
                    $offset = $start;
                }
                $body = substr($textData, $offset, $span);
                yield new TextToken($body);
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
                // no closing tag found... tread the rest of the text
                // as bbcode-body and break
                $body = substr($textData, $start);
                yield new CodeToken($name, $attrs, $body);
                break;
            }
            $body = substr($textData, $start, ($span - $start));
            yield new CodeToken($name, $attrs, $body);
            $offset = $span + \strlen($close);
        }
    }

    /**
     * parses an attribute string from a bbcode.
     *
     * @param string $attrs
     *
     * @return TokenAttrs
     */
    private function parseAttrs(string $attrs): TokenAttrs
    {
        $pairs = [];
        $match = $attrs;

        if (empty($attrs = trim($attrs))) {
            return new TokenAttrs($match, $pairs);
        }

        if ('=' === substr($attrs, 0, 1)) {
            // [bbcode=value] notation
            $pairs['@value'] = substr($attrs, 1);
            return new TokenAttrs($match, $pairs);
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

        return new TokenAttrs($match, $pairs);
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
     * @param Token       $token
     * @param string|null $body
     *
     * @return string
     */
    public function exportToken(Token $token, ?string $body): string
    {
        if ($token instanceof TextToken) {
            return $body ?: $token->getBody();
        }

        \assert($token instanceof CodeToken);
        $bbcode = $token->getName();
        $buffer = "[{$bbcode}";

        $attrMap = $token->getAttrs();
        if ($attrMap->hasMatch()) {
            $buffer .= $attrMap->getMatch();
        }

        $buffer .= ']';
        $buffer .= $body ?: $token->getBody();
        return $buffer . "[/{$bbcode}]";
    }
}
