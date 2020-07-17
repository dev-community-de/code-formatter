<?php

declare(strict_types=1);
namespace DevCommunityDE\CodeFormatter\Tests\Parser;

use DevCommunityDE\CodeFormatter\Exceptions\Exception;
use DevCommunityDE\CodeFormatter\Parser\ElemNode;
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
     * @testdox Parsing a non-existent file should throw
     *
     * @return void
     */
    public function testFileParsing()
    {
        $input = __DIR__ . '/non-existent.txt';
        $this->assertFalse(file_exists($input));
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('unable to open file: ' . $input);
        $this->parseFileToArray($input);
    }

    /**
     * @testdox PLAIN bbcodes should consume other bbcodes
     *
     * @return void
     */
    public function testPlainElement()
    {
        $nodes = $this->parseTextToArray(
            'before' .
            '[plain][code]test[/code][/plain]' .
            'after'
        )
        ;
        $this->assertCount(3, $nodes);

        $node0 = $nodes[0];
        \assert($node0 instanceof TextNode);
        $this->assertEquals('before', $node0->getBody());

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

        $node2 = $nodes[2];
        \assert($node2 instanceof TextNode);
        $this->assertEquals('after', $node2->getBody());
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
        $nodes = $this->parseTextToArray(
            'before' .
            '[code=rich]' .
                'hello' .
                '[code=css].test { color: red; }[/code]' .
                'world' .
            '[/code]' .
            'after'
        );

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

    /**
     * @testdox Check nested CODE=rich elements
     *
     * @return void
     */
    public function testNestedRichCode()
    {
        $nodes = $this->parseTextToArray(
            'before' .
            '[code=rich]' .
                '[code lang="rich" title="inner"]' .
                    '[code]test[/code]' .
                '[/code]' .
                'between' .
                '[code=rich][b]test2[/b][/code]' .
            '[/code]' .
            'after'
        );

        $this->assertCount(3, $nodes);

        $node0 = $nodes[0];
        \assert($node0 instanceof TextNode);
        $this->assertEquals('before', $node0->getBody());

        $node1 = $nodes[1];
        \assert($node1 instanceof ElemNode);
        $this->assertTrue($node1->isCode());
        $this->assertTrue($node1->isRich());
        $this->assertEquals('rich', $node1->getLang());
        $this->assertEquals('=rich', $node1->getAttrMatch());
        $this->assertEquals('rich', $node1->getAttr('@value'));

        $nodeList = $node1->getBody();
        \assert($nodeList instanceof NodeList);

        $elemBody = $nodeList->toArray();
        $this->assertCount(3, $elemBody);

        $subNode0 = $elemBody[0];
        \assert($subNode0 instanceof ElemNode);
        $this->assertTrue($subNode0->isCode());
        $this->assertTrue($subNode0->isRich());
        $this->assertEquals('rich', $subNode0->getLang());
        $this->assertEquals('rich', $subNode0->getAttr('lang'));
        $this->assertEquals('inner', $subNode0->getAttr('title'));
        $this->assertEquals(' lang="rich" title="inner"', $subNode0->getAttrMatch());
        $this->assertNull($subNode0->getAttr('@value'));

        $subNodeList = $subNode0->getBody();
        \assert($subNodeList instanceof NodeList);

        $subElemBody = $subNodeList->toArray();
        $this->assertCount(1, $subElemBody);

        $subSubNode0 = $subElemBody[0];
        \assert($subSubNode0 instanceof ElemNode);
        $this->assertTrue($subSubNode0->isCode());
        $this->assertFalse($subSubNode0->isRich());

        $subSubElemBody = $subSubNode0->getBody();
        $this->assertIsString($subSubElemBody);
        $this->assertEquals('test', $subSubElemBody);

        $subNode1 = $elemBody[1];
        \assert($subNode1 instanceof TextNode);
        $this->assertEquals('between', $subNode1->getBody());

        $subNode2 = $elemBody[2];
        \assert($subNode2 instanceof ElemNode);
        $this->assertTrue($subNode2->isCode());
        $this->assertTrue($subNode2->isRich());
        $this->assertEquals('rich', $subNode2->getLang());
        $this->assertNull($subNode2->getAttr('lang'));
        $this->assertEquals('=rich', $subNode2->getAttrMatch());
        $this->assertEquals('rich', $subNode2->getAttr('@value'));

        $subNodeList = $subNode2->getBody();
        \assert($subNodeList instanceof NodeList);

        $subElemBody = $subNodeList->toArray();
        $this->assertCount(1, $subElemBody);

        $subSubNode0 = $subElemBody[0];
        \assert($subSubNode0 instanceof TextNode);
        $this->assertEquals('[b]test2[/b]', $subSubNode0->getBody());

        $node2 = $nodes[2];
        \assert($node2 instanceof TextNode);
        $this->assertEquals('after', $node2->getBody());
    }

    /**
     * @testdox The parser should make the best out of invalid inputs
     *
     * @return void
     */
    public function testInvalidInputs()
    {
        $nodes = $this->parseTextToArray('[code lang=""""title="test"ing][/code]');
        $this->assertCount(1, $nodes);

        // TODO XenForo does not recognize this as a bbcode ...
        $node0 = $nodes[0];
        \assert($node0 instanceof ElemNode);
        $this->assertTrue($node0->isCode());
        $this->assertFalse($node0->isRich());

        // the `lang=""` part gets parsed correctly, because the parser
        // matched the attribute string not containing white-space (\S+)
        $this->assertEquals('', $node0->getLang());
        $this->assertEquals('', $node0->getAttr('lang'));

        $this->assertEquals(' lang=""""title="test"ing', $node0->getAttrMatch());
        $this->assertNull($node0->getAttr('@value'));
        $this->assertNull($node0->getAttr('title'));

        $parser = new Parser();
        $export = $parser->exportNode($node0, null);
        $this->assertEquals('[code lang=""""title="test"ing][/code]', $export);

        $nodes = $this->parseTextToArray('[code lang= """"title="test"ing][/code]');
        // note the white-space ---------------------^
        $this->assertCount(1, $nodes);

        $node0 = $nodes[0];
        \assert($node0 instanceof TextNode);
        $this->assertEquals('[code lang= """"title="test"ing][/code]', $node0->getBody());
    }
}
