<?php

declare(strict_types=1);

/*
 * This file is part of the DevCom Async Code Formatter
 * (c) The dev-community.de authors
 */

namespace DevCommunityDE\CodeFormatter\Parser;

use DevCommunityDE\CodeFormatter\Exceptions\Exception;

/**
 * this parser uses pcre (regular expressions)
 * to parse incoming code.
 */
final class PregParser implements Parser
{
    /** pattern used to tokenize the text */
    private const RE_SPLIT = '/\[(?:\\/code|code(=\w+|(?:\s+\w+=(?:"[^"]*"|\S+))*))\]/i';

    /** pattern used to tokenize attributes */
    private const RE_ATTRS = '/\G\s*(\w+)=("[^"]*"|\S+)/';

    /**
     * {@inheritdoc}
     *
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

        yield from $this->parseText($textData);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $textData
     *
     * @return @return iterable<Token>
     */
    public function parseText(string $textData): iterable
    {
        // the structure looks like this after the split:
        // 0 => text
        // 1 => code-tag attributes
        // 2 => code-tag body
        // 3 => text
        // 4 => ...

        $split = preg_split(self::RE_SPLIT, $textData, -1,
            PREG_SPLIT_DELIM_CAPTURE);

        if (0 === ($count = \count($split))) {
            return;
        }

        $index = 0;
        while ($index < $count) {
            $attrs = null;

            if (0 !== $index % 3) {
                $attrs = $this->parseAttrs($split[$index++]);
                $kind = Token::T_CODE;
            } else {
                $kind = Token::T_TEXT;
            }

            $body = $split[$index++];
            yield new Token($kind, $body, $attrs);
        }
    }

    /**
     * parses an attribute string from a code-tag.
     *
     * @param string $attrs
     *
     * @return array<string,string>|null
     */
    private function parseAttrs(string $attrs): ?array
    {
        // ensure a language
        $attrMap = ['lang' => 'text'];

        if (empty($attrs = trim($attrs))) {
            return $attrMap;
        }

        if ('=' === substr($attrs, 0, 1)) {
            // [code=lang] notation
            $attrMap['lang'] = substr($attrs, 1);
            return $attrMap;
        }

        $offset = 0;
        $length = \strlen($attrs);
        while ($offset < $length) {
            if (!preg_match(self::RE_ATTRS, $attrs, $m, 0, $offset)) {
                // we could throw here, but the text-input is in most
                // cases provided by users. so we just discard it
                break;
            }

            $attrMap[$m[1]] = $this->parseAttr($m[2]);
            $offset += \strlen($m[0]);
        }

        return $attrMap;
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
     * {@inheritdoc}
     *
     * @param Token       $token
     * @param string|null $body
     *
     * @return string
     */
    public function exportToken(Token $token, ?string $body): string
    {
        if ($token->isText()) {
            return $body ?: $token->getBody();
        }

        $buffer = '[code';
        $attrMap = $token->getAttributes();

        foreach ($attrMap as $name => $value) {
            $buffer .= ' ' . $name . '="' . $value . '"';
        }

        $buffer .= ']';
        $buffer .= $body ?: $token->getBody();
        return $buffer . '[/code]';
    }
}
