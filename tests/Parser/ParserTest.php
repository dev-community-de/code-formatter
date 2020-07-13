<?php

declare(strict_types=1);
namespace DevCommunityDE\CodeFormatter\Tests\Parser;

use DevCommunityDE\CodeFormatter\Parser\ElemNode;
use DevCommunityDE\CodeFormatter\Parser\Node;
use DevCommunityDE\CodeFormatter\Parser\Parser;
use DevCommunityDE\CodeFormatter\Parser\TextNode;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    /**
     * parses a file and returns it as array of nodes.
     *
     * @param string $input
     *
     * @return Node[]
     */
    private function parseFileToArray(string $input): array
    {
        $parser = new Parser();
        return iterator_to_array($parser->parseFile($input));
    }

    /**
     * parses a text and returns it as array of nodes.
     *
     * @param string $input
     *
     * @return Node[]
     */
    private function parseTextToArray(string $input): array
    {
        $parser = new Parser();
        return iterator_to_array($parser->parseText($input));
    }

    /**
     * @testdox Parsing a file should behave the same as parsing text
     *
     * @return void
     */
    public function testFileTextParsing()
    {
        $input = __DIR__ . '/input.txt';
        $fromText = $this->parseTextToArray(file_get_contents($input));
        $fromFile = $this->parseFileToArray($input);
        $this->assertEquals($fromText, $fromFile);
    }

    /**
     * @testdox PLAIN bbcodes should consume other bbcodes
     *
     * @return void
     */
    public function testPlainElement()
    {
        $nodes = $this->parseTextToArray('before plain bbcode[plain][code]test[/code][/plain]');
        $this->assertCount(2, $nodes);
        $this->assertInstanceof(TextNode::class, $nodes[0]);
        $this->assertEquals(Node::KIND_TEXT, $nodes[0]->getKind());
        $this->assertEquals('before plain bbcode', $nodes[0]->getBody());
        $this->assertInstanceof(ElemNode::class, $nodes[1]);
        $this->assertNotTrue($nodes[1]->isCode());
        $this->assertEquals('[code]test[/code]', $nodes[1]->getBody());
    }

    /**
     * @testdox Brackets inside text shouldn't yield additional text-nodes
     *
     * @return void
     */
    public function testBracketsInsideText()
    {
        $nodes = $this->parseTextToArray('hello[[[[[');
        $this->assertCount(1, $nodes);
        $this->assertInstanceof(TextNode::class, $nodes[0]);
        $this->assertEquals('hello[[[[[', $nodes[0]->getBody());

        $nodes = $this->parseTextToArray('hello[[[[[code]test[/code]');
        $this->assertCount(2, $nodes);
        $this->assertInstanceof(TextNode::class, $nodes[0]);
        $this->assertEquals('hello[[[[', $nodes[0]->getBody());
        $this->assertInstanceof(ElemNode::class, $nodes[1]);
        $this->assertTrue($nodes[1]->isCode());
        $this->assertEquals('test', $nodes[1]->getBody());
    }

    /**
     * @testdox Unmatched closing bbcodes should be treated as text
     *
     * @return void
     */
    public function testUnmatchedClosingElements()
    {
        $nodes = $this->parseTextToArray('[code]a[/code]b[/code]');
        $this->assertCount(2, $nodes);
        $this->assertInstanceOf(ElemNode::class, $nodes[0]);
        $this->assertTrue($nodes[0]->isCode());
        $this->assertEquals('a', $nodes[0]->getBody());
        $this->assertInstanceOf(TextNode::class, $nodes[1]);
        $this->assertEquals('b[/code]', $nodes[1]->getBody());
    }

    /**
     * @testdox Nested CODE bbcodes should behave the same as in XenForo
     *
     * @return void
     */
    public function testNestedCodeElements()
    {
        $nodes = $this->parseTextToArray('[code][code]test[/code][/code]');
        $this->assertCount(2, $nodes);
        $this->assertInstanceof(ElemNode::class, $nodes[0]);
        $this->assertTrue($nodes[0]->isCode());
        $this->assertEquals('[code]test', $nodes[0]->getBody());
        $this->assertInstanceof(TextNode::class, $nodes[1]);
        $this->assertEquals('[/code]', $nodes[1]->getBody());
    }

    /**
     * @testdox Unclosed bbcodes should be matched correctly
     *
     * @return void
     */
    public function testUnclosedElements()
    {
        $nodes = $this->parseTextToArray('[code]test');
        $this->assertCount(1, $nodes);
        $this->assertInstanceOf(ElemNode::class, $nodes[0]);
        $this->assertTrue($nodes[0]->isCode());
        $this->assertEquals('test', $nodes[0]->getBody());

        // TODO we could add an algorithm that enables this:
        //
        // $nodes = $this->parseTextToArray('[code]a[code]b[/code]');
        // //=> [ code(a), code(b) ];
        // $this->assertCount(2, $nodes);
        // $this->assertInstanceOf(ElemNode::class, $nodes[0]);
        // $this->assertTrue($nodes[0]->isCode());
        // $this->assertEquals('a', $nodes[0]->getBody());
        // $this->assertInstanceOf(ElemNode::class, $nodes[1]);
        // $this->assertTrue($nodes[1]->isCode());
        // $this->assertEquals('b', $nodes[1]->getBody());
    }

    /**
     * @testdox Verify that attributes are parsed correctly and are exported as-is
     *
     * @return void
     */
    public function testAttributeBehaviour()
    {
        $nodes = $this->parseTextToArray('[code=css]test{}[/code]');
        $this->assertCount(1, $nodes);
        $this->assertInstanceOf(ElemNode::class, $nodes[0]);
        $this->assertEquals('=css', $nodes[0]->getAttrMatch());
        $this->assertEquals('css', $nodes[0]->getAttr('@value'));

        $nodes = $this->parseTextToArray('[code lang="css" title="Test 123"]test{}[/code]');
        $this->assertCount(1, $nodes);
        $this->assertInstanceOf(ElemNode::class, $nodes[0]);
        $this->assertEquals(' lang="css" title="Test 123"', $nodes[0]->getAttrMatch());
        $this->assertEquals('css', $nodes[0]->getAttr('lang'));
        $this->assertEquals('Test 123', $nodes[0]->getAttr('title'));
    }

    /**
     * @testdox Verify that parsed bbcodes are exported as-is
     *
     * @return void
     */
    public function testElementExports()
    {
        $parser = new Parser();
        $inputs = [
            '[code=css]test{}[/code]',
            '[code lang=css title="Test"]test{}[/code]',
            '[plain][code=css]test{}[/code][/plain]',
            '[CODE]test[/CODE]',
        ];

        foreach ($inputs as $input) {
            $nodes = $this->parseTextToArray($input);
            $this->assertCount(1, $nodes);
            $this->assertInstanceOf(ElemNode::class, $nodes[0]);
            $export = $parser->exportNode($nodes[0], null);
            $this->assertEquals($input, $export);
        }
    }
}
