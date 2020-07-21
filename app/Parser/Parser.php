<?php

declare(strict_types=1);

namespace DevCommunityDE\CodeFormatter\Parser;

use DevCommunityDE\CodeFormatter\Exceptions\Exception;

final class Parser
{
    /** pattern used to tokenize bbcode elements */
    private const RE_ELEM = '/\G\[(code|plain)(=\w+|(?:\s+\w+=(?:"[^"]*"|\S+?))*)\]/i';

    /** pattern used to tokenize attributes */
    private const RE_ATTR = '/\G\s*(\w+)=("[^"]*"|\S+)/';

    /**
     * @param string $filePath
     *
     * @return iterable<Node>
     */
    public function parseFile(string $filePath): iterable
    {
        // note: we cannot stat php://input
        // therefore we have to give it a try
        $fileHandle = @fopen($filePath, 'r');

        if (false === $fileHandle) {
            throw new Exception('unable to open file: ' . $filePath);
        }

        $textData = stream_get_contents($fileHandle);
        fclose($fileHandle);

        if (false === $textData) {
            throw new Exception('unable to read file: ' . $filePath);
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

            \assert($node instanceof TextNode);
            $textBuffer .= $node->getBody();
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
            $elemName = $m[1];
            $elemAttrs = $this->parseAttrs($m[2]);
            $bodyStart = $offset + \strlen($m[0]);

            if ($this->isRichCode($elemName, $elemAttrs)) {
                // CODE=rich allows nested CODE bbcodes ... yay.
                // switch to the recursive parser and collect all
                // child-nodes as tag-body
                $state->offset = $bodyStart;
                $elemBody = $this->parseRich($state);
                return new ElemNode($elemName, $elemAttrs, $elemBody);
            }

            $closeTag = "[/{$elemName}]";
            $closePos = stripos($input, $closeTag, $bodyStart);

            if (false === $closePos) {
                // edge case:
                // no closing tag found... treat the rest of the input
                // as bbcode-body and stop the parsing entirely
                $elemBody = substr($input, $bodyStart) ?: '';
                $state->finish();
                return new ElemNode($elemName, $elemAttrs, $elemBody);
            }

            $bodySpan = $closePos - $bodyStart;
            $elemBody = substr($input, $bodyStart, $bodySpan) ?: '';
            $state->offset = $closePos + \strlen($closeTag);
            return new ElemNode($elemName, $elemAttrs, $elemBody);
        }

        $textSpan = max(1, strcspn($input, '[', $offset));
        $textBody = substr($input, $offset, $textSpan) ?: '';
        $state->offset += (int) $textSpan;
        return new TextNode($textBody);
    }

    /**
     * parses a CODE=rich body.
     *
     * @param State $state
     *
     * @return NodeList
     */
    private function parseRich(State $state): NodeList
    {
        $nodeList = new NodeList();
        $textBuffer = '';
        while ($state->valid()) {
            $node = $this->parseNode($state);

            if ($node instanceof ElemNode) {
                // yield a buffered text-node first (if any)
                if (!empty($textBuffer)) {
                    $nodeList->append($textBuffer);
                    $textBuffer = '';
                }

                $nodeList->append($node);
                continue;
            }

            \assert($node instanceof TextNode);
            $textBody = $node->getBody();

            if ('[' === $textBody) {
                // a single bracket was parsed as text-node
                $input = $state->input;
                $offset = $state->offset;

                if (0 === substr_compare($input, '/code]', $offset, 6, true)) {
                    // found a closing tag, stop rich-parsing
                    $state->offset += 6;
                    break;
                }
            }

            $textBuffer .= $textBody;
        }

        if (!empty($textBuffer)) {
            // append a buffered text-node (if any)
            $nodeList->append($textBuffer);
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
        $buffer .= $body ?: $this->exportBody($node->getBody());
        return $buffer . "[/{$bbcode}]";
    }

    /**
     * exports a node body.
     *
     * @param NodeList|string $nodeBody
     *
     * @return string
     */
    private function exportBody($nodeBody): string
    {
        if (\is_string($nodeBody)) {
            return $nodeBody;
        }

        \assert($nodeBody instanceof NodeList);

        $buffer = '';
        foreach ($nodeBody as $subNode) {
            $buffer .= $this->exportNode($subNode, null);
        }
        return $buffer;
    }
}
