<?php

declare(strict_types=1);
namespace DevCommunityDE\CodeFormatter\Tests\Parser;

use DevCommunityDE\CodeFormatter\Parser\ElemNode;
use DevCommunityDE\CodeFormatter\Parser\Node;
use DevCommunityDE\CodeFormatter\Parser\NodeList;
use DevCommunityDE\CodeFormatter\Parser\Parser;
use DevCommunityDE\CodeFormatter\Parser\TextNode;
use DevCommunityDE\CodeFormatter\Tests\Parser\Helpers\ParserTestHelpers;
use PHPUnit\Framework\TestCase;
use Traversable;

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

        $node0 = $nodes[0];
        \assert($node0 instanceof TextNode);
        $this->assertEquals(Node::KIND_TEXT, $node0->getKind());
        $this->assertEquals('before plain bbcode', $node0->getBody());

        $node1 = $nodes[1];
        \assert($node1 instanceof ElemNode);
        $this->assertNotTrue($node1->isCode());
        $this->assertNotTrue($node1->isRich());
        $this->assertNull($node1->getLang());
        $this->assertEquals('plain', $node1->getName());
        $this->assertEquals('', $node1->getAttrMatch());

        $elemBody = $node1->getBody();
        $this->assertIsString($elemBody);
        $this->assertEquals('[code]test[/code]', $elemBody);
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

        $node0 = $nodes[0];
        \assert($node0 instanceof TextNode);
        $this->assertEquals('hello[[[[[', $node0->getBody());

        $nodes = $this->parseTextToArray('hello[[[[[code]test[/code]');
        $this->assertCount(2, $nodes);

        $node0 = $nodes[0];
        \assert($node0 instanceof TextNode);
        $this->assertEquals('hello[[[[', $node0->getBody());

        $node1 = $nodes[1];
        \assert($node1 instanceof ElemNode);
        $this->assertTrue($node1->isCode());
        $this->assertFalse($node1->isRich());
        $this->assertNull($node1->getLang());
        $this->assertEquals('', $node1->getAttrMatch());

        $elemBody = $node1->getBody();
        $this->assertIsString($elemBody);
        $this->assertEquals('test', $elemBody);
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

        $node0 = $nodes[0];
        \assert($node0 instanceof ElemNode);
        $this->assertTrue($node0->isCode());
        $this->assertFalse($node0->isRich());
        $this->assertNull($node0->getLang());
        $this->assertEquals('', $node0->getAttrMatch());

        $elemBody = $node0->getBody();
        $this->assertIsString($elemBody);
        $this->assertEquals('a', $elemBody);

        $node1 = $nodes[1];
        \assert($node1 instanceof TextNode);
        $this->assertEquals('b[/code]', $node1->getBody());
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

        $node0 = $nodes[0];
        \assert($node0 instanceof ElemNode);
        $this->assertTrue($node0->isCode());
        $this->assertFalse($node0->isRich());
        $this->assertNull($node0->getLang());
        $this->assertEquals('', $node0->getAttrMatch());

        $elemBody = $node0->getBody();
        $this->assertIsString($elemBody);
        $this->assertEquals('[code]test', $elemBody);

        $node1 = $nodes[1];
        \assert($node1 instanceof TextNode);
        $this->assertEquals('[/code]', $node1->getBody());
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

        $node0 = $nodes[0];
        \assert($node0 instanceof ElemNode);
        $this->assertTrue($node0->isCode());
        $this->assertFalse($node0->isRich());
        $this->assertNull($node0->getLang());
        $this->assertEquals('', $node0->getAttrMatch());

        $elemBody = $node0->getBody();
        $this->assertIsString($elemBody);
        $this->assertEquals('test', $elemBody);

        // it would be possible to treat any opening bbcode
        // as an implicit closing bbcode (if another bbcode is open).
        // currently, the parser just consumes everything ignoring
        // other bbcodes inside.
        $nodes = $this->parseTextToArray('[code]a[plain]b[/plain]');
        $this->assertCount(1, $nodes);

        $node0 = $nodes[0];
        \assert($node0 instanceof ElemNode);
        $this->assertTrue($node0->isCode());

        $elemBody = $node0->getBody();
        $this->assertIsString($elemBody);
        $this->assertEquals('a[plain]b[/plain]', $elemBody);
    }

    /**
     * @testdox Verify that attributes are parsed correctly and are exported as-is
     *
     * @return void
     */
    public function testAttributeParsing()
    {
        $nodes = $this->parseTextToArray('[code=css]test{}[/code]');
        $this->assertCount(1, $nodes);

        $node0 = $nodes[0];
        \assert($node0 instanceof ElemNode);
        $this->assertTrue($node0->isCode());
        $this->assertFalse($node0->isRich());
        $this->assertEquals('css', $node0->getLang());
        $this->assertEquals('=css', $node0->getAttrMatch());
        $this->assertEquals('css', $node0->getAttr('@value'));

        $elemBody = $node0->getBody();
        $this->assertIsString($elemBody);
        $this->assertEquals('test{}', $elemBody);

        $nodes = $this->parseTextToArray('[code lang="css" title="Test 123"]test{}[/code]');
        $this->assertCount(1, $nodes);

        $node0 = $nodes[0];
        \assert($node0 instanceof ElemNode);
        $this->assertTrue($node0->isCode());
        $this->assertFalse($node0->isRich());
        $this->assertEquals('css', $node0->getLang());
        $this->assertEquals(' lang="css" title="Test 123"', $node0->getAttrMatch());
        $this->assertEquals('css', $node0->getAttr('lang'));
        $this->assertEquals('Test 123', $node0->getAttr('title'));

        $elemBody = $node0->getBody();
        $this->assertIsString($elemBody);
        $this->assertEquals('test{}', $elemBody);
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
            '[code]test[/code]',
            '[code=rich][code]test[/code]test[/code]',
            '[CODE=css]test[[[[/CODE]',
        ];

        foreach ($inputs as $input) {
            $nodes = $this->parseTextToArray($input);
            $this->assertCount(1, $nodes);
            $this->assertInstanceOf(ElemNode::class, $nodes[0]);
            $export = $parser->exportNode($nodes[0], null);
            $this->assertEquals($input, $export);
        }
    }

    /**
     * @testdox Verify that CODE=rich is parsed correctly
     *
     * @return void
     */
    public function testRichCode()
    {
        $nodes = $this->parseTextToArray('before[code=rich]hello[code=css].test { color: red; }[/code]world[/code]after');
        $this->assertCount(3, $nodes);

        $node0 = $nodes[0];
        \assert($node0 instanceof TextNode);
        $this->assertEquals('before', $node0->getBody());

        $node2 = $nodes[2];
        \assert($node2 instanceof TextNode);
        $this->assertEquals('after', $node2->getBody());

        $node1 = $nodes[1];
        \assert($node1 instanceof ElemNode);
        $this->assertTrue($node1->isCode());
        $this->assertTrue($node1->isRich());
        $this->assertEquals('rich', $node1->getLang());
        $this->assertEquals('=rich', $node1->getAttrMatch());
        $this->assertEquals('rich', $node1->getAttr('@value'));

        $nodeList = $node1->getBody();
        \assert($nodeList instanceof NodeList);
        $nodeIter = $nodeList->getIterator();
        $this->assertInstanceOf(Traversable::class, $nodeIter);

        $nodeListArray = iterator_to_array($nodeIter);
        $this->assertIsArray($nodeListArray);

        $elemBody = $nodeList->toArray();
        $this->assertIsArray($elemBody);
        $this->assertCount(3, $elemBody);
        $this->assertEquals($elemBody, $nodeListArray);

        $subNode0 = $elemBody[0];
        \assert($subNode0 instanceof TextNode);
        $this->assertEquals('hello', $subNode0->getBody());

        $subNode1 = $elemBody[1];
        \assert($subNode1 instanceof ElemNode);
        $this->assertTrue($subNode1->isCode());
        $this->assertFalse($subNode1->isRich());
        $this->assertEquals('css', $subNode1->getLang());
        $this->assertEquals('=css', $subNode1->getAttrMatch());
        $this->assertEquals('css', $subNode1->getAttr('@value'));

        $subElemBody = $subNode1->getBody();
        $this->assertIsString($subElemBody);
        $this->assertEquals('.test { color: red; }', $subElemBody);

        $subNode2 = $elemBody[2];
        \assert($subNode2 instanceof TextNode);
        $this->assertEquals('world', $subNode2->getBody());
    }
}
