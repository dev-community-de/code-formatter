<?php

namespace DevCommunityDE\CodeFormatter\CodeFormatter;

use DevCommunityDE\CodeFormatter\Exceptions\Exception;

/**
 * Class CodeFormatter
 *
 * @package DevCommunityDE\CodeFormatter\CodeFormatter
 */
abstract class CodeFormatter
{

    /**
     * @var array
     */
    protected static $code_formatters = [
        ClangCodeFormatter::class,
    ];

    /**
     * @var array
     */
    protected static $supported_languages = [];

    /**
     * @param string $code_language
     * @return self
     */
    public static function create(string $code_language) : self
    {
        return self::getCodeFormatterForLanguage($code_language);
    }

     /**
     * @param string $lang
     * @return self
     */
    protected static function getCodeFormatterForLanguage(string $lang) : self
    {
        foreach (self::$code_formatters as $code_formatter) {
            if (self::supportsLanguage($code_formatter, $lang)) {
                return new $code_formatter;
            }
        }
    }

    /**
     * @param string $code_formatter
     * @param string $lang
     * @return bool
     */
    protected static function supportsLanguage(string $code_formatter, string $lang) : bool
    {
        return in_array($lang, $code_formatter::$supported_languages);
    }

    /**
     * @param string $file
     */
    public function exec(string $file)
    {
        // execute code formatting
        exec($this->getShellCommand($file), $output, $return_var);

        if ($return_var !== 0) {
            throw new Exception('code formatting failed');
        }
    }

    /**
     * @param string $file
     * @return string
     */
    abstract protected function getShellCommand(string $file);

}
