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
     *
     */
    public function run()
    {
        $this->readPostContent();

        $this->splitPostAtCodeBlockEnding();

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
    protected function splitPostAtCodeBlockEnding()
    {
        // split post at code block ending
        $this->post_content = preg_split('/\[\/CODE\]/', $this->post_content);
    }

    /**
     *
     */
    protected function processPostContent()
    {
        foreach ($this->post_content as $key => $block) {
            if ($this->isCodeBlock($block)) {
                $file = $this->putCodeInTempFile($key);

                $this->executeCodeFormatting($file);

                $code = $this->getFormattedCode($file);

                $this->post_content[$key] = $this->replaceCodeBlockWithFormattedCode($code, $block);

                $this->deleteTempCodeFile($file);
            }
        }

        // recompose post content
        $this->post_content = implode('[/CODE]', $this->post_content);
    }

    /**
     * @param string $str
     * @return bool
     */
    protected function isCodeBlock(string $str) : bool
    {
        // check if code block
        $is_code_block = preg_match('/(\[CODE(?:=([a-z]+)?)?\])((?:.*\n?)*)/', $str, $matches) === 1;

        $this->captureCodeBlockComponents($matches);

        return $is_code_block;
    }

    /**
     * @param array $matches
     */
    protected function captureCodeBlockComponents(array $matches)
    {
        // capture code block components (code tag, code language, actual code content)
        $this->code_tag = $matches[1];
        $this->code_language = $matches[2] ?: 'txt';
        $this->code_content = $matches[3];
    }

    /**
     * @param string $file_key
     * @return string
     */
    protected function putCodeInTempFile(string $file_key = '') : string
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

        $code_formatter->exec($file);
    }

    /**
     * @param string $file
     * @return string
     */
    protected function getFormattedCode(string $file) : string
    {
        // get formatted code
        return $this->code_tag . file_get_contents($file);
    }

    /**
     * @param string $formatted_code
     * @param string $code_block
     * @return string
     */
    protected function replaceCodeBlockWithFormattedCode(string $formatted_code, string $code_block) : string
    {
        // replace code block with formatted code, fall back to code block
        return preg_replace('/(\[CODE(?:=([a-z]+)?)?\])((?:.*\n?)*)/', $formatted_code, $code_block) ?? $code_block;
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
