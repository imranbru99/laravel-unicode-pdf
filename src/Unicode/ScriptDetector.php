<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Unicode;

use ImranDev\UnicodePdf\Contracts\ScriptDetectorInterface;

class ScriptDetector implements ScriptDetectorInterface
{
    /**
     * Unicode script definitions with matching regex patterns.
     *
     * @var array<string, string>
     */
    protected static array $scriptPatterns = [
        'Bengali' => '/[\x{0980}-\x{09FF}]/u',
        'Arabic' => '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u',
        'Devanagari' => '/[\x{0900}-\x{097F}\x{A8E0}-\x{A8FF}]/u',
        'Tamil' => '/[\x{0B80}-\x{0BFF}]/u',
        'Telugu' => '/[\x{0C00}-\x{0C7F}]/u',
        'Malayalam' => '/[\x{0D00}-\x{0D7F}]/u',
        'Gujarati' => '/[\x{0A80}-\x{0AFF}]/u',
        'Gurmukhi' => '/[\x{0A00}-\x{0A7F}]/u',
        'Kannada' => '/[\x{0C80}-\x{0CFF}]/u',
        'Sinhala' => '/[\x{0D80}-\x{0DFF}]/u',
        'Thai' => '/[\x{0E00}-\x{0E7F}]/u',
        'Hebrew' => '/[\x{0590}-\x{05FF}\x{FB1D}-\x{FB4F}]/u',
        'Cyrillic' => '/[\x{0400}-\x{04FF}\x{0500}-\x{052F}]/u',
        'Greek' => '/[\x{0370}-\x{03FF}\x{1F00}-\x{1FFF}]/u',
        'Japanese' => '/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u',
        'Korean' => '/[\x{AC00}-\x{D7AF}\x{1100}-\x{11FF}\x{3130}-\x{318F}]/u',
        'CJK' => '/[\x{4E00}-\x{9FFF}\x{3400}-\x{4DBF}\x{F900}-\x{FAFF}]/u',
        'Armenian' => '/[\x{0530}-\x{058F}]/u',
        'Georgian' => '/[\x{10A0}-\x{10FF}\x{2D00}-\x{2D2F}]/u',
        'Ethiopic' => '/[\x{1200}-\x{137F}\x{1380}-\x{139F}\x{2D80}-\x{2DDF}\x{AB00}-\x{AB2F}]/u',
        'Khmer' => '/[\x{1780}-\x{17FF}\x{19E0}-\x{19FF}]/u',
        'Myanmar' => '/[\x{1000}-\x{109F}\x{AA60}-\x{AA7F}\x{A9E0}-\x{A9FF}]/u',
        'Lao' => '/[\x{0E80}-\x{0EFF}]/u',
        'Tibetan' => '/[\x{0F00}-\x{0FFF}]/u',
        'Mongolian' => '/[\x{1800}-\x{18AF}]/u',
        'Thaana' => '/[\x{0780}-\x{07BF}]/u',
        'Nko' => '/[\x{07C0}-\x{07FF}]/u',
        'Adlam' => '/[\x{1E900}-\x{1E95F}]/u',
        'Syriac' => '/[\x{0700}-\x{074F}]/u',
        'Javanese' => '/[\x{A980}-\x{A9DF}]/u',
        'Balinese' => '/[\x{1B00}-\x{1B7F}]/u',
        'Sundanese' => '/[\x{1B80}-\x{1BBF}]/u',
        'Yi' => '/[\x{A000}-\x{A48F}]/u',
        'Cherokee' => '/[\x{13A0}-\x{13FF}\x{AB70}-\x{ABBF}]/u',
        'Emoji' => '/[\x{1F300}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u',
        'Latin' => '/[a-zA-Z\x{00C0}-\x{024F}\x{1E00}-\x{1EFF}]/u',
    ];

    /**
     * Detect all distinct scripts present in the text and their character counts.
     *
     * @return array<string, int>
     */
    public function detect(string $text): array
    {
        $cleanText = strip_tags($text);
        if (trim($cleanText) === '') {
            return [];
        }

        $results = [];

        foreach (self::$scriptPatterns as $script => $pattern) {
            $count = preg_match_all($pattern, $cleanText);
            if ($count !== false && $count > 0) {
                $results[$script] = $count;
            }
        }

        arsort($results);

        return $results;
    }

    /**
     * Get the dominant script name for the text.
     */
    public function getDominantScript(string $text): string
    {
        $detected = $this->detect($text);
        if (empty($detected)) {
            return 'Latin';
        }

        return (string) array_key_first($detected);
    }

    /**
     * Check if the text contains characters of a specific script.
     */
    public function containsScript(string $text, string $script): bool
    {
        $cleanText = strip_tags($text);
        if (isset(self::$scriptPatterns[$script])) {
            return (bool) preg_match(self::$scriptPatterns[$script], $cleanText);
        }

        return false;
    }

    /**
     * Check if text contains any complex script requiring font shaping.
     */
    public function containsComplexScript(string $text): bool
    {
        $complexScripts = [
            'Bengali', 'Arabic', 'Devanagari', 'Tamil', 'Telugu', 'Malayalam',
            'Gujarati', 'Gurmukhi', 'Kannada', 'Sinhala', 'Thai', 'Khmer',
            'Myanmar', 'Lao', 'Tibetan', 'Javanese', 'Balinese', 'Sundanese',
            'Thaana', 'Nko', 'Adlam', 'Syriac',
        ];

        foreach ($complexScripts as $script) {
            if ($this->containsScript($text, $script)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Register a custom script pattern.
     */
    public function registerScript(string $name, string $regexPattern): void
    {
        self::$scriptPatterns[$name] = $regexPattern;
    }
}
