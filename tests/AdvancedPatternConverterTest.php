<?php

namespace GregPriday\GitIgnore\Tests;

use GregPriday\GitIgnore\PatternConverter;

class AdvancedPatternConverterTest extends BaseTestCase
{
    private PatternConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new PatternConverter;
    }

    /**
     * @dataProvider providerBasicCharacterClasses
     */
    public function test_basic_character_classes(string $pattern, string $subject, bool $expected, string $message): void
    {
        $regex = $this->converter->patternToRegex($pattern);
        $result = (bool) preg_match($regex, $subject);
        $this->assertSame($expected, $result, "Pattern '{$pattern}' applied to '{$subject}': {$message}");
    }

    public function providerBasicCharacterClasses(): array
    {
        return [
            ['[a]bc.txt', 'abc.txt', true, 'Single character class [a] should match the specific character "a" in "abc.txt"'],
            ['[a]bc.txt', 'bbc.txt', false, 'Single character class [a] should not match "b" at the beginning of "bbc.txt"'],
            ['[a-z]est.txt', 'test.txt', true, 'Range [a-z] should match any lowercase letter including "t" in "test.txt"'],
            ['[a-z]est.txt', 'Test.txt', false, 'Range [a-z] should not match uppercase "T" in "Test.txt" (case-sensitive)'],
            ['[A-Z]est.txt', 'Test.txt', true, 'Range [A-Z] should match any uppercase letter including "T" in "Test.txt"'],
            ['[A-Z]est.txt', 'test.txt', false, 'Range [A-Z] should not match lowercase "t" in "test.txt" (case-sensitive)'],
            ['file[0-9].log', 'file5.log', true, 'Numeric range [0-9] should match digit "5" in "file5.log"'],
            ['file[0-9].log', 'fileA.log', false, 'Numeric range [0-9] should not match letter "A" in "fileA.log"'],
            ['[a-zA-Z]file.txt', 'afile.txt', true, 'Multiple ranges [a-zA-Z] should match lowercase "a" in "afile.txt"'],
            ['[a-zA-Z]file.txt', 'Afile.txt', true, 'Multiple ranges [a-zA-Z] should match uppercase "A" in "Afile.txt"'],
            ['[a-zA-Z]file.txt', '1file.txt', false, 'Multiple ranges [a-zA-Z] should not match digit "1" in "1file.txt"'],
        ];
    }

    /**
     * @dataProvider providerNegatedCharacterClasses
     */
    public function test_negated_character_classes(string $pattern, string $subject, bool $expected, string $message): void
    {
        $regex = $this->converter->patternToRegex($pattern);
        $result = (bool) preg_match($regex, $subject);
        $this->assertSame($expected, $result, "Pattern '{$pattern}' applied to '{$subject}': {$message}");
    }

    public function providerNegatedCharacterClasses(): array
    {
        return [
            ['[!a-z]file.txt', 'Afile.txt', true, 'Negated range [!a-z] should match uppercase "A" in "Afile.txt" as it\'s not a lowercase letter'],
            ['[!a-z]file.txt', 'afile.txt', false, 'Negated range [!a-z] should not match lowercase "a" in "afile.txt" as it\'s explicitly excluded'],
            ['[^a-z]file.txt', '1file.txt', true, 'Negated range with ^ syntax [^a-z] should match digit "1" in "1file.txt" as it\'s not a lowercase letter'],
            ['[^a-z]file.txt', 'afile.txt', false, 'Negated range with ^ syntax [^a-z] should not match lowercase "a" in "afile.txt" as it\'s explicitly excluded'],
            ['[!a-z0-9]file.txt', '#file.txt', true, 'Complex negated range [!a-z0-9] should match special character "#" in "#file.txt" as it\'s neither lowercase nor digit'],
            ['[!a-z0-9]file.txt', 'afile.txt', false, 'Complex negated range [!a-z0-9] should not match lowercase "a" in "afile.txt" as it\'s explicitly excluded'],
            ['[!a-z0-9]file.txt', '5file.txt', false, 'Complex negated range [!a-z0-9] should not match digit "5" in "5file.txt" as it\'s explicitly excluded'],
        ];
    }

    /**
     * @dataProvider providerSpecialCharactersInClasses
     */
    public function test_special_characters_in_classes(string $pattern, string $subject, bool $expected, string $message): void
    {
        $regex = $this->converter->patternToRegex($pattern);
        $result = (bool) preg_match($regex, $subject);
        $this->assertSame($expected, $result, "Pattern '{$pattern}' applied to '{$subject}': {$message}");
    }

    public function providerSpecialCharactersInClasses(): array
    {
        return [
            ['file[.+]name.txt', 'file.name.txt', true, 'Character class with special regex chars [.+] should match literal "." in "file.name.txt"'],
            ['file[.+]name.txt', 'file+name.txt', true, 'Character class with special regex chars [.+] should match literal "+" in "file+name.txt"'],
            ['file[.+]name.txt', 'filename.txt', false, 'Character class with special regex chars [.+] should not match when no "." or "+" is present'],
            ['[_!@#]special.txt', '_special.txt', true, 'Character class with multiple special chars [_!@#] should match underscore in "_special.txt"'],
            ['[_!@#]special.txt', '@special.txt', true, 'Character class with multiple special chars [_!@#] should match at sign in "@special.txt"'],
            ['[_!@#]special.txt', 'xspecial.txt', false, 'Character class with multiple special chars [_!@#] should not match "x" in "xspecial.txt"'],
            ['[-abc]dash.txt', '-dash.txt', true, 'Dash at the beginning of character class [-abc] should match literal dash in "-dash.txt"'],
            ['[-abc]dash.txt', 'adash.txt', true, 'Character class with dash at beginning [-abc] should still match other listed chars like "a" in "adash.txt"'],
            ['[abc-]dash.txt', '-dash.txt', true, 'Dash at the end of character class [abc-] should match literal dash in "-dash.txt"'],
        ];
    }

    /**
     * @dataProvider providerCharacterClassesWithOtherFeatures
     */
    public function test_character_classes_with_other_features(string $pattern, string $subject, bool $expected, string $message): void
    {
        $regex = $this->converter->patternToRegex($pattern);
        $result = (bool) preg_match($regex, $subject);
        $this->assertSame($expected, $result, "Pattern '{$pattern}' applied to '{$subject}': {$message}");
    }

    public function providerCharacterClassesWithOtherFeatures(): array
    {
        return [
            ['[a-z]*.txt', 'abc.txt', true, 'Character class [a-z] combined with wildcard should match "abc.txt" starting with lowercase letter'],
            ['[a-z]*.txt', 'Abc.txt', false, 'Character class [a-z] combined with wildcard should not match "Abc.txt" starting with uppercase letter'],
            ['**/[a-z]*.txt', 'dir/abc.txt', true, 'Double asterisk with character class should match file in subdirectory "dir/abc.txt" starting with lowercase'],
            ['**/[a-z]*.txt', 'dir/subdir/abc.txt', true, 'Double asterisk with character class should match deeply nested file "dir/subdir/abc.txt" starting with lowercase'],
            ['**/[a-z]*.txt', 'dir/Abc.txt', false, 'Double asterisk with character class should not match "dir/Abc.txt" starting with uppercase, despite directory matching'],
            ['{[a-z]*,[A-Z]*}.txt', 'abc.txt', true, 'Brace expansion with character classes should match "abc.txt" via the [a-z]* pattern'],
            ['{[a-z]*,[A-Z]*}.txt', 'ABC.txt', true, 'Brace expansion with character classes should match "ABC.txt" via the [A-Z]* pattern'],
            ['{[a-z]*,[A-Z]*}.txt', '123.txt', false, 'Brace expansion with character classes should not match "123.txt" as neither pattern includes digits'],
            ['[a-z][0-9]*.txt', 'a1file.txt', true, 'Multiple character classes should match "a1file.txt" with lowercase letter followed by digit'],
            ['[a-z][0-9]*.txt', 'ab.txt', false, 'Multiple character classes require all constraints to match - "ab.txt" fails the digit requirement'],
            ['[a-z][0-9]*.txt', 'A1file.txt', false, 'Multiple character classes are case-sensitive - "A1file.txt" fails the lowercase requirement'],
        ];
    }

    /**
     * @dataProvider providerCharacterClassEdgeCases
     */
    public function test_character_class_edge_cases(string $pattern, string $subject, bool $expected, string $message): void
    {
        $regex = $this->converter->patternToRegex($pattern);
        $result = (bool) preg_match($regex, $subject);
        $this->assertSame($expected, $result, "Pattern '{$pattern}' applied to '{$subject}': {$message}");
    }

    public function providerCharacterClassEdgeCases(): array
    {
        return [
            ['[]file.txt', 'file.txt', false, 'Empty character class [] should match nothing, so even "file.txt" without the empty class prefix should not match'],
            ['[\\]\\[]\\.txt', '].txt', true, 'Escaped brackets inside character class [\\]\\[] should match literal right bracket in "].txt"'],
            ['[\\]\\[]\\.txt', '[.txt', true, 'Escaped brackets inside character class [\\]\\[] should match literal left bracket in "[.txt"'],
            ['[\\]\\[]\\.txt', 'a.txt', false, 'Escaped brackets inside character class [\\]\\[] should not match normal letter in "a.txt"'],
            ['[\\-]\\.txt', '-.txt', true, 'Escaped dash [\\-] should match literal dash in "-.txt" without treating it as a range operator'],
            ['[!]file.txt', '!file.txt', true, 'Character class containing only "!" [!] should match literal exclamation mark in "!file.txt", not act as negation'],
            ['[^]file.txt', '^file.txt', true, 'Character class containing only "^" [^] should match literal caret in "^file.txt", not act as negation'],
            ['[abc', '[abc', true, 'Unclosed character class "[abc" should be treated as a literal string, matching itself exactly'],
            ['[abc', 'abc', false, 'Unclosed character class "[abc" treated as literal should not match "abc" without the bracket'],
        ];
    }

    /**
     * Test direct conversion methods in PatternConverter.
     */
    public function test_direct_converter_methods(): void
    {
        // Test convertPatternToRegex
        $regex = $this->converter->convertPatternToRegex('[a-z]*.txt');
        $this->assertStringContainsString('[a-z]', $regex, 'convertPatternToRegex should preserve character class [a-z] in the resulting regex pattern');

        // Test with negated class
        $regex = $this->converter->convertPatternToRegex('[!a-z]*.txt');
        $this->assertStringContainsString('[^a-z]', $regex, 'convertPatternToRegex should transform negation symbol from ! to ^ in character classes for regex compatibility');

        // Test nested brace expansion with character classes
        $patterns = $this->converter->expandBraces('{[a-z]*,[A-Z]*}.{txt,log}');
        $this->assertCount(4, $patterns, 'expandBraces should correctly expand "{[a-z]*,[A-Z]*}.{txt,log}" into 4 distinct patterns');
        $this->assertContains('[a-z]*.txt', $patterns, 'expandBraces should preserve character classes in the expanded patterns');
        $this->assertContains('[A-Z]*.log', $patterns, 'expandBraces should correctly handle complex nested expansions with character classes');
    }
}
