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

    public function testParsingAFileBehavesTheSameAsParsingAText()
    {
        $input = __DIR__ . '/input-0.txt';
        $fromText = $this->parseTextToArray(file_get_contents($input));
        $fromFile = $this->parseFileToArray($input);
        $this->assertEquals($fromText, $fromFile);
    }

    public function testPlainBbcodeConsumesOtherBbcodes()
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

    public function testBracketsInsideTextAreHandledCorrectly()
    {
        $nodes = $this->parseTextToArray('hello[[[[[');
        $this->assertCount(2, $nodes);
        $this->assertInstanceof(TextNode::class, $nodes[0]);
        $this->assertEquals('hello', $nodes[0]->getBody());
        $this->assertInstanceof(TextNode::class, $nodes[1]);
        $this->assertEquals('[[[[[', $nodes[1]->getBody());

        $nodes = $this->parseTextToArray('hello[[[[[code]test[/code]');
        $this->assertCount(3, $nodes);
        $this->assertInstanceof(TextNode::class, $nodes[0]);
        $this->assertEquals('hello', $nodes[0]->getBody());
        $this->assertInstanceof(TextNode::class, $nodes[1]);
        $this->assertEquals('[[[[', $nodes[1]->getBody());
        $this->assertInstanceof(ElemNode::class, $nodes[2]);
        $this->assertTrue($nodes[2]->isCode());
        $this->assertEquals('test', $nodes[2]->getBody());
    }

    public function testNestedCodeBbcodesBehaveTheSameAsInXenforo()
    {
        $nodes = $this->parseTextToArray('[code][code]test[/code][/code]');
        $this->assertCount(3, $nodes);
        $this->assertInstanceof(ElemNode::class, $nodes[0]);
        $this->assertTrue($nodes[0]->isCode());
        $this->assertEquals('[code]test', $nodes[0]->getBody());

        // TODO could be optimized as single text-node "[/code]"
        $this->assertInstanceof(TextNode::class, $nodes[1]);
        $this->assertEquals('[', $nodes[1]->getBody());
        $this->assertInstanceof(TextNode::class, $nodes[2]);
        $this->assertEquals('/code]', $nodes[2]->getBody());
    }

    public function testUnclosedBbcodesGetParsedCorrectly()
    {
        $nodes = $this->parseTextToArray('[code]test');
        $this->assertCount(1, $nodes);
        $this->assertInstanceOf(ElemNode::class, $nodes[0]);
        $this->assertTrue($nodes[0]->isCode());
        $this->assertEquals('test', $nodes[0]->getBody());
    }

    public function testAttributesGetMatchedCorrectly()
    {
        $nodes = $this->parseTextToArray('[code=css]test{}[/code]');
        $this->assertCount(1, $nodes);
        $this->assertInstanceOf(ElemNode::class, $nodes[0]);
        $this->assertEquals('=css', $nodes[0]->getAttrs()->getMatch());
        $this->assertEquals('=css', (string) $nodes[0]->getAttrs());
        $this->assertEquals('css', $nodes[0]->getAttr('@value'));

        $nodes = $this->parseTextToArray('[code lang="css" title="Test 123"]test{}[/code]');
        $this->assertCount(1, $nodes);
        $this->assertInstanceOf(ElemNode::class, $nodes[0]);
        $this->assertEquals(' lang="css" title="Test 123"', (string) $nodes[0]->getAttrs());
        $this->assertEquals('css', $nodes[0]->getAttr('lang'));
        $this->assertEquals('Test 123', $nodes[0]->getAttr('title'));
    }

    public function testParsedBbcodesAreExportedAsIs()
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
