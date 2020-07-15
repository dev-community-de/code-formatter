<?php

declare(strict_types=1);

namespace DevCommunityDE\CodeFormatter\Parser;

use DevCommunityDE\CodeFormatter\Exceptions\Exception;

/**
 * uses as a parse-state.
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

/**
 * this parser uses pcre (regular expressions)
 * to parse incoming code.
 */
final class Parser
{
    /** pattern used to tokenize bbcode elements */
    private const RE_ELEM = '/\G\[(code|plain)(=\w+|(?:\s+\w+=(?:"[^"]*"|\S+))*)\]/i';

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
     * parses the given text.
     *
     * @param string $textData
     *
     * @return iterable<Node>
     */
    public function parseText(string $textData): iterable
    {
        $state = new State($textData);
        return $this->parseNorm($state);
    }

    /**
     * parses and normalizes a prepared state.
     *
     * @param State $state
     *
     * @return iterable<Node>
     */
    private function parseNorm(State $state): iterable
    {
        $textBuffer = '';
        while ($state->valid()) {
            $node = $this->parseNode($state);

            if ($node instanceof ElemNode) {
                // yield a buffered text-node first (if any)
                if (!empty($textBuffer)) {
                    yield new TextNode($textBuffer);
                    $textBuffer = '';
                }

                yield $node;
                continue;
            }

            $textBody = $node->getBody();
            \assert(\is_string($textBody));
            $textBuffer .= $textBody;
        }

        if (!empty($textBuffer)) {
            // yield a buffered text-node (if any)
            yield new TextNode($textBuffer);
        }
    }

    /**
     * parses a node.
     *
     * @param State $state
     *
     * @return Node
     */
    private function parseNode(State $state): Node
    {
        $input = $state->input;
        $offset = $state->offset;

        if (preg_match(self::RE_ELEM, $input, $m, 0, $offset)) {
            // read from $textData until we see a closing tag
            $tagName = $m[1];
            $tagAttrs = $this->parseAttrs($m[2]);
            $bodyStart = $offset + \strlen($m[0]);

            if ($this->isRichCode($tagName, $tagAttrs)) {
                // CODE=rich allows nested CODE bbcodes ... yay.
                // switch to the recursive parser and collect all
                // child-nodes as tag-body
                $state->offset = $bodyStart;
                $tagBody = $this->parseRich($state);
                return new ElemNode($tagName, $tagAttrs, $tagBody);
            }

            $closeTag = "[/{$tagName}]";
            $closePos = stripos($input, $closeTag, $bodyStart);

            if (false === $closePos) {
                // edge case:
                // no closing tag found... treat the rest of the input
                // as bbcode-body and stop the parsing entirely
                $tagBody = substr($input, $bodyStart) ?: '';
                $state->finish();
                return new ElemNode($tagName, $tagAttrs, $tagBody);
            }

            $bodySpan = $closePos - $bodyStart;
            $tagBody = substr($input, $bodyStart, $bodySpan) ?: '';
            $state->offset = $closePos + \strlen($closeTag);
            return new ElemNode($tagName, $tagAttrs, $tagBody);
        }

        $textSpan = max(1, strcspn($input, '[', $offset));
        $textBody = substr($input, $offset, $textSpan);
        $state->offset += $textSpan;
        return new TextNode($textBody);
    }

    /**
     * parses a CODE=rich body.
     *
     * @param State $state
     *
     * @return Node[]
     */
    private function parseRich(State $state): array
    {
        $nodeList = [];
        $textBuffer = '';
        while ($state->valid()) {
            $node = $this->parseNode($state);

            if ($node instanceof ElemNode) {
                // yield a buffered text-node first (if any)
                if (!empty($textBuffer)) {
                    $nodes[] = new TextNode($textBuffer);
                    $textBuffer = '';
                }

                $nodes[] = $node;
                continue;
            }

            $textBody = $node->getBody();
            \assert(\is_string($textBody));

            if ('[' === $textBody) {
                // a single bracket was parsed a text-node
                $input = $state->input;
                $offset = $state->offset;
                $closePos = stripos($input, '/code]', $offset);

                if ($closePos === $offset) {
                    // found a closing tag, stop rich-parsing
                    $state->offset += \strlen('/code]');
                    break;
                }
            }

            $textBuffer .= $textBody;
        }

        if (!empty($textBuffer)) {
            // append a buffered text-node (if any)
            $nodeList[] = new TextNode($textBuffer);
        }

        return $nodeList;
    }

    /**
     * checks if the given node is a CODE=rich node.
     *
     * @param string    $tagName
     * @param ElemAttrs $tagAttrs
     *
     * @return bool
     */
    private function isRichCode(string $tagName, ElemAttrs $tagAttrs): bool
    {
        if (0 !== strcasecmp($tagName, 'code')) {
            return false;
        }

        $lang = $tagAttrs->getValue('lang') ?:
                $tagAttrs->getValue('@value');

        return $lang && 0 === strcasecmp($lang, 'rich');
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
