<?php

declare(strict_types=1);
namespace DevCommunityDE\CodeFormatter\Tests\Parser;

use DevCommunityDE\CodeFormatter\Parser\ElemNode;
use DevCommunityDE\CodeFormatter\Parser\Node;
use DevCommunityDE\CodeFormatter\Parser\Parser;
use DevCommunityDE\CodeFormatter\Parser\TextNode;
use DevCommunityDE\CodeFormatter\Tests\Parser\Helpers\ParserTestHelpers;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    use ParserTestHelpers;

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
     * @testdox Unclosed bbcodes should consume the rest of the input as body
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

        // it would be possible to treat any opening bbcode
        // as an implicit closing bbcode (if another bbcode is open).
        // currently, the parser just consumes everything ignoring
        // other bbcodes inside.
        $nodes = $this->parseTextToArray('[code]a[plain]b[/plain]');
        $this->assertCount(1, $nodes);
        $this->assertInstanceOf(ElemNode::class, $nodes[0]);
        $this->assertTrue($nodes[0]->isCode());
        $this->assertEquals('a[plain]b[/plain]', $nodes[0]->getBody());

        // an optimized algorithm could produce the following:
        //
        // $nodes = $this->parseTextToArray('[code]a[plain]b[/plain]');
        // $this->assertCount(2, $nodes);
        // $this->assertInstanceOf(ElemNode::class, $nodes[0]);
        // $this->assertTrue($nodes[0]->isCode());
        // $this->assertEquals('a', $nodes[0]->getBody());
        // $this->assertInstanceOf(ElemNode::class, $nodes[1]);
        // $this->assertEqualsIgnoringCase('plain', $nodes[1]->getName());
        // $this->assertEqual('b', $nodes[1]->getBody());
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
