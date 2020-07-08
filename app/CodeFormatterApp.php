<?php

namespace DevCommunityDE\CodeFormatter;

use DevCommunityDE\CodeFormatter\CodeFormatter\CodeFormatter;
use DevCommunityDE\CodeFormatter\Exceptions\Exception;
use DevCommunityDE\CodeFormatter\Parser\Parser;
use DevCommunityDE\CodeFormatter\Parser\PregParser;
use DevCommunityDE\CodeFormatter\Parser\Token;

/**
 * Class CodeFormatterApp.
 */
class CodeFormatterApp
{
    /**
     * code_lang => file_ext mappings.
     *
     * @var array
     */
    private const EXT_MAPPING = [
        'c' => 'c',
        'cpp' => 'cpp',
        'csharp' => 'cs',
        'css' => 'css',
        'go' => 'go',
        'html' => 'html',
        'java' => 'java',
        'javascript' => 'js',
        'json' => 'json',
        'less' => 'less',
        'markdown' => 'md',
        'objectivec' => 'm',
        'php' => 'php',
        'python' => 'py',
        'ruby' => 'rb',
        'sass' => 'sass',
        'scss' => 'scss',
        'sql' => 'sql',
        'swift' => 'swift',
        'yaml' => 'yaml',
    ];

    /** @var Parser */
    private $parser;

    /**
     * constructs the app.
     *
     * @param Parser|null $parser
     */
    public function __construct(Parser $parser = null)
    {
        $this->parser = $parser ?? new PregParser();
    }

    /**
     * runs the application.
     *
     * @return void
     */
    public function run()
    {
        $tokens = $this->parseInput();

        foreach ($tokens as $token) {
            echo $this->formatToken($token);
        }
    }

    /**
     * parses the code from stdin.
     *
     * @return iterable<Token>
     */
    private function parseInput(): iterable
    {
        return $this->parser->parseFile('php://input');
    }

    /**
     * formats a token based on its type and language.
     *
     * @param Token $token
     *
     * @return string
     */
    private function formatToken(Token $token): string
    {
        if ($token->isText()) {
            return $token->getBody();
        }

        $language = $token->getAttribute('lang');
        \assert(null !== $language);

        $formatter = CodeFormatter::create($language);
        if (null === $formatter) {
            // no formatter found, return token as is
            return $this->exportToken($token, null);
        }

        $filename = $this->createFilename($language);

        file_put_contents($filename, $token->getBody());
        $formatter->exec($filename);
        $result = file_get_contents($filename);
        unlink($filename);

        if (false === $result) {
            throw new Exception('could not read result from: ' . $filename);
        }

        return $this->exportToken($token, $result);
    }

    /**
     * exports a token.
     *
     * @param Token       $token
     * @param string|null $body
     *
     * @return string
     */
    private function exportToken(Token $token, ?string $body): string
    {
        return $this->parser->exportToken($token, $body);
    }

    /**
     * creates a (somewhat) unique filename used to dump the code
     * for the formatter.
     *
     * @param string $language
     *
     * @return string
     */
    private function createFilename(string $language): string
    {
        $extension = self::EXT_MAPPING[$language] ?? 'txt';
        $filename = tempnam(__DIR__ . '/../storage/code', 'code-formatter');
        rename($filename, $filename .= '.' . $extension);
        @chmod($filename, 0666);

        return $filename;
    }
}
