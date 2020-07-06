<?php

declare(strict_types=1);

/*
 * This file is part of the dev-community code formatter
 * (c) The dev-community.de authors
 */

namespace DevCommunityDE\CodeFormatter\Parser;

/**
 * note: this class is meant to be stateless to make
 * it reusable across requests.
 */
final class Parser implements ParserInterface
{
    // preg_split pattern
    private const RE_SPLIT = '/\[(?:\\/code|code(=\w+|(?:\s+\w+=(?:"(?:\\\\.|[^"]+)"|\S+))*))\]/i';

    /**
     * {@inheritdoc}
     *
     * @param string $file
     *
     * @return array
     */
    public function parse(string $file): array
    {
        $nodes = [];

        $source = file_get_contents($file);
        $split = preg_split(self::RE_SPLIT, $source, -1, PREG_SPLIT_DELIM_CAPTURE);

        if (empty($split)) {
            return $nodes;
        }

        $index = 0;
        $count = \count($split);
        while ($index < $count) {
            if (0 === $index % 3) {
                $body = $split[$index++];
                $nodes[] = new ParseNode(ParseNode::KIND_TEXT, $body);
            } else {
                $attr = $this->parseAttributes($split[$index++]);
                $body = $split[$index++];
                $nodes[] = new ParseNode(ParseNode::KIND_CODE, $body, $attr);
            }
        }

        return $nodes;
    }

    /**
     * {@inheritdoc}
     *
     * @param ParseNode   $node
     * @param string|null $body
     *
     * @return string
     */
    public function export(ParseNode $node, ?string $body): string
    {
        $buffer = '[code';
        $attrs = $node->getAttributes();

        if (1 === \count($attrs)) {
            // use "=lang" notation
            $buffer .= '=' . $attrs['lang'];
        } else {
            foreach ($attrs as $name => $value) {
                // TODO needs tests if the quoting can break
                $buffer .= " {$name}=\"{$value}\"";
            }
        }

        $buffer .= ']';
        $buffer .= $body ?: $node->getBody();
        return $buffer . '[/code]';
    }

    /**
     * parses code-tag attributes.
     *
     * @param string $text
     *
     * @return array
     */
    private function parseAttributes(string $text): array
    {
        $attrs = [];

        if ('=' === substr($text, 0, 1)) {
            // simple code tag
            $attrs['lang'] = substr($text, 1);
            return $attrs;
        }

        // TODO this is not the best way to tokenize attributes
        if (!preg_match_all('/\s+(\w+)=("(?:\\\\.|[^"]+)"|\S+)/', $text, $m)) {
            // TODO throw exception?
            return $attrs;
        }

        $length = \count($m[1]);
        for ($index = 0; $index < $length; ++$index) {
            $name = $m[1][$index];
            $value = $m[2][$index];
            // de-quote value if necessary
            switch (substr($value, 0, 1)) {
                case '"':
                case "'":
                    $value = substr($value, 1, -1);
                    break;
                default:
                    // noop
            }
            $attrs[$name] = $value;
        }

        return $attrs;
    }
}
