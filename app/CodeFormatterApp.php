<?php

namespace DevCommunityDE\CodeFormatter;

use DevCommunityDE\CodeFormatter\Exceptions\Exception;
use DevCommunityDE\CodeFormatter\CodeFormatter\CodeFormatter;

/**
 * Class CodeFormatterApp
 *
 * @package DevCommunityDE\CodeFormatter
 */
class CodeFormatterApp
{

    /**
     * @var string
     */
    protected const CODE_BLOCK_REGEX = '/(\[CODE(?:=([a-z]+)?)?\])((?:.*\n?)*)/';

    /**
     * @var string|array
     */
    protected $post_content;

    /**
     * @var string
     */
    protected $code_tag;

    /**
     * @var string
     */
    protected $code_language;

    /**
     * @var string
     */
    protected $code_content;

    /**
     * @var string
     */
    protected $code_file;

    /**
     *
     */
    public function run()
    {
        $this->readPostContent();

        $this->processPostContent();

        $this->outputPostContent();
    }

    /**
     *
     */
    protected function readPostContent()
    {
        // read raw input from request body
        $this->post_content = file_get_contents('php://input');
    }

    /**
     *
     */
    protected function processPostContent()
    {
        $this->splitPostAtCodeBlockEnding();

        foreach ($this->post_content as $key => $block) {
            try {
                $this->post_content[$key] = $this->replaceCodeBlockWithFormattedCodeBlock($block, $key);
            } catch (Exception $e) {
                $this->deleteTempCodeFile($this->code_file);

                continue;
            }
        }

        // recompose post content
        $this->post_content = implode('[/CODE]', $this->post_content);
    }

    /**
     *
     */
    protected function splitPostAtCodeBlockEnding()
    {
        // split post at code block ending
        $this->post_content = preg_split('/\[\/CODE\]/', $this->post_content);
    }

    /**
     * @param string $block
     * @param string $key
     * @return string
     */
    protected function replaceCodeBlockWithFormattedCodeBlock(string $block, string $key) : string
    {
        // replace code block with formatted code block
        return preg_replace_callback(self::CODE_BLOCK_REGEX, function (array $match) use ($key) : string {
            $this->captureCodeBlockComponents($match);

            return $this->code_tag . $this->formatCode($key);
        }, $block);
    }

    /**
     * @param array $match
     */
    protected function captureCodeBlockComponents(array $match)
    {
        // capture code block components (code tag, code language, actual code content)
        $this->code_tag = $match[1];
        $this->code_language = $match[2] ?: 'txt';
        $this->code_content = $match[3];
    }

    /**
     * @param string $file_key
     * @return string
     */
    protected function formatCode(string $file_key) : string
    {
        $this->code_file = $this->putCodeInTempFile($file_key);

        $this->executeCodeFormatting($this->code_file);

        $code = $this->getFormattedCode($this->code_file);

        $this->deleteTempCodeFile($this->code_file);

        return $code;
    }

    /**
     * @param string $file_key
     * @return string
     */
    protected function putCodeInTempFile(string $file_key) : string
    {
        $filename = $this->generateTempFileName($file_key);

        if (file_put_contents($filename, $this->code_content) === false) {
            throw new Exception('failed to store code in temporary file');
        }

        return $filename;
    }

    /**
     * @param string $file_key
     */
    protected function generateTempFileName(string $file_key)
    {
        // generate sha256-hashed unique filename based on code content and key for specific part of post content
        $filename = hash('sha256', $this->code_content . uniqid($file_key));
        $file_extension = '.' . $this->code_language;

        return __DIR__ . '/../storage/code/' . $filename . $file_extension;
    }

    /**
     * @param string $file
     */
    protected function executeCodeFormatting(string $file)
    {
        $code_formatter = CodeFormatter::create($this->code_language);

        if (!$code_formatter) {
            throw new Exception('No code formatter for given language found');
        }

        $code_formatter->exec($file);
    }

    /**
     * @param string $file
     * @return string
     */
    protected function getFormattedCode(string $file) : string
    {
        // get formatted code
        return file_get_contents($file);
    }

    /**
     * @param string $file
     */
    protected function deleteTempCodeFile(string $file)
    {
        // delete temporary code file
        unlink($file);
    }

    /**
     *
     */
    protected function outputPostContent()
    {
        echo $this->post_content;
    }

}
