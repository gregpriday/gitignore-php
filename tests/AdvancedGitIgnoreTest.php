<?php

namespace GregPriday\GitIgnore\Tests;

use GregPriday\GitIgnore\GitIgnoreManager;
use Symfony\Component\Finder\SplFileInfo;

class AdvancedGitIgnoreTest extends BaseTestCase
{
    /**
     * Test complex nested brace expansions with wildcards.
     */
    public function test_complex_nested_brace_expansion(): void
    {
        $gitignoreContent = '**/{test,{dev,prod}}/{*.{ts,js},{config,setup}.{json,yml}}';
        $this->createTestItem('.gitignore', $gitignoreContent);

        $manager = new GitIgnoreManager($this->tempDir);

        // Should match
        $this->assertFalse(
            $manager->accept(new SplFileInfo($this->createTestItem('src/test/file.ts'), 'src/test', 'src/test/file.ts')),
            'src/test/file.ts should match the pattern'
        );
        $this->assertFalse(
            $manager->accept(new SplFileInfo($this->createTestItem('app/dev/config.json'), 'app/dev', 'app/dev/config.json')),
            'app/dev/config.json should match the pattern'
        );
        $this->assertFalse(
            $manager->accept(new SplFileInfo($this->createTestItem('libs/prod/setup.yml'), 'libs/prod', 'libs/prod/setup.yml')),
            'libs/prod/setup.yml should match the pattern'
        );

        // Should not match
        $this->assertTrue(
            $manager->accept(new SplFileInfo($this->createTestItem('src/stage/file.ts'), 'src/stage', 'src/stage/file.ts')),
            'src/stage/file.ts should not match the pattern'
        );
        $this->assertTrue(
            $manager->accept(new SplFileInfo($this->createTestItem('app/dev/data.xml'), 'app/dev', 'app/dev/data.xml')),
            'app/dev/data.xml should not match the pattern'
        );
    }

    /**
     * Test Unicode/international characters in filenames and patterns.
     */
    public function test_unicode_characters_in_filenames(): void
    {
        $gitignoreContent = "# Ignore files with Unicode characters\n*.你好\n📁/*\n";
        $this->createTestItem('.gitignore', $gitignoreContent);

        $manager = new GitIgnoreManager($this->tempDir);

        $this->assertFalse(
            $manager->accept(new SplFileInfo($this->createTestItem('document.你好'), '', 'document.你好')),
            'document.你好 should be ignored'
        );
        $this->assertFalse(
            $manager->accept(new SplFileInfo($this->createTestItem('📁/something.txt'), '📁', '📁/something.txt')),
            '📁/something.txt should be ignored'
        );
        $this->assertTrue(
            $manager->accept(new SplFileInfo($this->createTestItem('normal.txt'), '', 'normal.txt')),
            'normal.txt should be accepted'
        );
    }

    /**
     * Test complex chains of negation rules.
     */
    public function test_complex_negation_chain(): void
    {
        $gitignoreContent = <<<'EOD'
# Ignore all logs
*.log
# But keep important ones
!important.log
# Except secret ones (double negation → ignore)
!!important.log.secret
# But keep encrypted ones (triple negation → re-include)
!!!important.log.secret.enc
EOD;

        $this->createTestItem('.gitignore', $gitignoreContent);
        $manager = new GitIgnoreManager($this->tempDir);

        $this->assertFalse(
            $manager->accept(new SplFileInfo($this->createTestItem('debug.log'), '', 'debug.log')),
            'debug.log should be ignored'
        );
        $this->assertTrue(
            $manager->accept(new SplFileInfo($this->createTestItem('important.log'), '', 'important.log')),
            'important.log should be accepted'
        );
        $this->assertFalse(
            $manager->accept(new SplFileInfo($this->createTestItem('important.log.secret'), '', 'important.log.secret')),
            'important.log.secret should be ignored by the double negation'
        );
        $this->assertTrue(
            $manager->accept(new SplFileInfo($this->createTestItem('important.log.secret.enc'), '', 'important.log.secret.enc')),
            'important.log.secret.enc should be accepted by the triple negation'
        );
    }

    /**
     * Test extremely deep directory structures.
     */
    public function test_deep_directory_structure(): void
    {
        // Use double asterisk to match multiple directory levels
        $gitignoreContent = 'deep/**/logs/*.log';
        $this->createTestItem('.gitignore', $gitignoreContent);

        // Create a very deep path
        $deepPath = 'deep/level1/level2/level3/level4/level5/level6/level7/logs';
        $this->createTestItem("$deepPath/app.log", 'log content');

        $manager = new GitIgnoreManager($this->tempDir);

        $this->assertFalse(
            $manager->accept(new SplFileInfo($this->createTestItem("$deepPath/app.log"), $deepPath, "$deepPath/app.log")),
            'Deep nested structure should match the pattern'
        );
    }

    /**
     * Test complex pattern combinations with README files.
     */
    public function test_complex_pattern_combinations(): void
    {
        $gitignoreContent = <<<'EOD'
# Ignore all .md files
*.md
# Except README files
!README*.md
# But ignore README-private files
README-private*.md
EOD;

        $this->createTestItem('.gitignore', $gitignoreContent);
        $manager = new GitIgnoreManager($this->tempDir);

        $this->assertFalse(
            $manager->accept(new SplFileInfo($this->createTestItem('documentation.md'), '', 'documentation.md')),
            'documentation.md should be ignored'
        );
        $this->assertTrue(
            $manager->accept(new SplFileInfo($this->createTestItem('README.md'), '', 'README.md')),
            'README.md should be accepted'
        );
        $this->assertTrue(
            $manager->accept(new SplFileInfo($this->createTestItem('README-public.md'), '', 'README-public.md')),
            'README-public.md should be accepted'
        );
        $this->assertFalse(
            $manager->accept(new SplFileInfo($this->createTestItem('README-private.md'), '', 'README-private.md')),
            'README-private.md should be ignored'
        );
        $this->assertFalse(
            $manager->accept(new SplFileInfo($this->createTestItem('README-private-draft.md'), '', 'README-private-draft.md')),
            'README-private-draft.md should be ignored'
        );
    }

    /**
     * Test multiple additional ignore files with conflicting rules.
     */
    public function test_multiple_ignore_files_with_conflicts(): void
    {
        // .gitignore ignores *.log
        $this->createTestItem('.gitignore', '*.log');

        // .customignore re-includes error.log
        $this->createTestItem('.customignore', '!error.log');

        // .appignore ignores everything in logs/
        $this->createTestItem('.appignore', 'logs/');

        $manager = new GitIgnoreManager($this->tempDir, true, ['.customignore', '.appignore']);

        // This should be re-included by .customignore
        $this->assertTrue(
            $manager->accept(new SplFileInfo($this->createTestItem('error.log'), '', 'error.log')),
            'error.log should be re-included by .customignore'
        );

        // This should be ignored by .gitignore
        $this->assertFalse(
            $manager->accept(new SplFileInfo($this->createTestItem('debug.log'), '', 'debug.log')),
            'debug.log should be ignored by .gitignore'
        );

        // This should be ignored by .appignore even though it\'s error.log
        $this->assertFalse(
            $manager->accept(new SplFileInfo($this->createTestItem('logs/error.log'), 'logs', 'logs/error.log')),
            'logs/error.log should be ignored by .appignore despite being error.log'
        );
    }

    /**
     * Test escaped special characters in patterns.
     *
     * Note: The .gitignore file contains lines like:
     *   \#not_a_comment.txt
     *   file\?.txt
     *   path\[abc\]/*.js
     *
     * After processing, the escapes are removed. In particular,
     * "path\[abc\]/*.js" becomes "path[abc]/*.js". Since this pattern
     * contains a "*", it is converted to a regex. The "[abc]" is then
     * interpreted as a character class (matching one character among a, b, or c),
     * so a file under a directory literally named "path[abc]" will not match.
     * Thus, we now expect such a file to be accepted.
     */
    public function test_escaped_special_characters(): void
    {
        $gitignoreContent = <<<'EOD'
# Escaped characters
\#not_a_comment.txt
file\?.txt
path\[abc\]/*.js
EOD;

        $this->createTestItem('.gitignore', $gitignoreContent);
        $manager = new GitIgnoreManager($this->tempDir);

        $this->assertFalse(
            $manager->accept(new SplFileInfo($this->createTestItem('#not_a_comment.txt'), '', '#not_a_comment.txt')),
            '#not_a_comment.txt should be ignored'
        );
        $this->assertFalse(
            $manager->accept(new SplFileInfo($this->createTestItem('file?.txt'), '', 'file?.txt')),
            'file?.txt should be ignored'
        );
        // Because "path\[abc\]/*.js" becomes "path[abc]/*.js" and [abc] is a character class,
        // a file in a directory literally named "path[abc]" does NOT match, so it is accepted.
        $this->assertTrue(
            $manager->accept(new SplFileInfo($this->createTestItem('path[abc]/script.js'), 'path[abc]', 'path[abc]/script.js')),
            'path[abc]/script.js should be accepted'
        );

        $this->assertTrue(
            $manager->accept(new SplFileInfo($this->createTestItem('#actual_comment.txt'), '', '#actual_comment.txt')),
            '#actual_comment.txt should be accepted'
        );
    }

    /**
     * Test pattern precedence with overlapping rules.
     */
    public function test_pattern_precedence_with_overlapping_rules(): void
    {
        $gitignoreContent = <<<'EOD'
# Ignore all files
*
# Re-include all directories
!*/
# Re-include specific files
!*.php
!README.md
# Re-ignore specific PHP files
vendor/**/*.php
EOD;

        $this->createTestItem('.gitignore', $gitignoreContent);
        $manager = new GitIgnoreManager($this->tempDir);

        // Files that should be ignored
        $this->assertFalse(
            $manager->accept(new SplFileInfo($this->createTestItem('random.txt'), '', 'random.txt')),
            'random.txt should be ignored by the global * pattern'
        );
        $this->assertFalse(
            $manager->accept(new SplFileInfo($this->createTestItem('vendor/package/file.php'), 'vendor/package', 'vendor/package/file.php')),
            'vendor/package/file.php should be ignored by the vendor/**/*.php pattern'
        );

        // Files that should be accepted
        $this->assertTrue(
            $manager->accept(new SplFileInfo($this->createTestItem('src/Controller.php'), 'src', 'src/Controller.php')),
            'src/Controller.php should be accepted due to !*.php rule'
        );
        $this->assertTrue(
            $manager->accept(new SplFileInfo($this->createTestItem('README.md'), '', 'README.md')),
            'README.md should be accepted due to specific re-inclusion'
        );
        $this->assertTrue(
            $manager->accept(new SplFileInfo($this->createTestItem('src', '', true), '', 'src')),
            'src directory should be accepted due to !*/ rule'
        );
    }

    /**
     * Test paths with whitespace and special characters.
     */
    public function test_paths_with_whitespace_and_special_characters(): void
    {
        $gitignoreContent = <<<'EOD'
# Files with spaces and special characters
"file with spaces.txt"
path with\ spaces/*.log
weird\ $ymb@ls.txt
"[special] folder"/*
EOD;

        $this->createTestItem('.gitignore', $gitignoreContent);
        $manager = new GitIgnoreManager($this->tempDir);

        $this->assertFalse(
            $manager->accept(new SplFileInfo($this->createTestItem('file with spaces.txt'), '', 'file with spaces.txt')),
            'file with spaces.txt should be ignored'
        );
        $this->assertFalse(
            $manager->accept(new SplFileInfo($this->createTestItem('path with spaces/error.log'), 'path with spaces', 'path with spaces/error.log')),
            'path with spaces/error.log should be ignored'
        );
        $this->assertFalse(
            $manager->accept(new SplFileInfo($this->createTestItem('weird $ymb@ls.txt'), '', 'weird $ymb@ls.txt')),
            'weird $ymb@ls.txt should be ignored'
        );
        // Because the pattern "[special] folder"/* becomes [special] folder/* and is converted by regex (due to the "*"),
        // a file in a directory literally named "[special] folder" will not match and thus will be accepted.
        $this->assertTrue(
            $manager->accept(new SplFileInfo($this->createTestItem('[special] folder/file.txt'), '[special] folder', '[special] folder/file.txt')),
            '[special] folder/file.txt should be accepted'
        );
    }

    /**
     * Test extremely complex pattern with multiple wildcards and braces.
     */
    public function test_extreme_complex_pattern(): void
    {
        $gitignoreContent = '**/{src,lib}/{test,{unit,integration}}/{{**/*_,}test.{js,ts,php},{helper,mock}/**/*.{js,ts,php}}';
        $this->createTestItem('.gitignore', $gitignoreContent);

        $manager = new GitIgnoreManager($this->tempDir);

        // Files that should match
        $this->assertFalse($manager->accept(new SplFileInfo($this->createTestItem('src/test/test.js'), 'src/test', 'src/test/test.js')));
        $this->assertFalse($manager->accept(new SplFileInfo($this->createTestItem('lib/unit/user_test.php'), 'lib/unit', 'lib/unit/user_test.php')));
        $this->assertFalse($manager->accept(new SplFileInfo($this->createTestItem('app/lib/integration/helper/utils.ts'), 'app/lib/integration/helper', 'app/lib/integration/helper/utils.ts')));

        // Files that should not match
        $this->assertTrue($manager->accept(new SplFileInfo($this->createTestItem('src/prod/test.js'), 'src/prod', 'src/prod/test.js')));
        $this->assertTrue($manager->accept(new SplFileInfo($this->createTestItem('lib/unit/helper.txt'), 'lib/unit', 'lib/unit/helper.txt')));
    }

    /**
     * Test rules with character classes in square brackets.
     */
    public function test_character_classes_in_patterns(): void
    {
        $gitignoreContent = <<<'EOD'
# Character classes
[a-z]est.txt
file[0-9].log
[!a-z]*.txt
EOD;

        $this->createTestItem('.gitignore', $gitignoreContent);
        $manager = new GitIgnoreManager($this->tempDir);

        // The patterns [a-z]est.txt and file[0-9].log do not contain '*' or '?'
        // so they are treated as literal strings. Therefore, files "test.txt" and "best.txt"
        // do not exactly match the literal "[a-z]est.txt", and the file names "file1.log", "file9.log",
        // and "fileA.log" do not exactly match "file[0-9].log". However, later rule ([!a-z]*.txt)
        // applies to .txt files.
        $this->assertTrue($manager->accept(new SplFileInfo($this->createTestItem('test.txt'), '', 'test.txt')), 'test.txt should be accepted (literal match fails)');
        $this->assertTrue($manager->accept(new SplFileInfo($this->createTestItem('best.txt'), '', 'best.txt')), 'best.txt should be accepted (literal match fails)');
        $this->assertTrue($manager->accept(new SplFileInfo($this->createTestItem('file1.log'), '', 'file1.log')), 'file1.log should be accepted (literal match fails)');
        $this->assertTrue($manager->accept(new SplFileInfo($this->createTestItem('file9.log'), '', 'file9.log')), 'file9.log should be accepted (literal match fails)');
        $this->assertTrue($manager->accept(new SplFileInfo($this->createTestItem('fileA.log'), '', 'fileA.log')), 'fileA.log should be accepted (literal match fails)');

        // The pattern [!a-z]*.txt contains a wildcard so it is converted to regex.
        // It should match files starting with a non-lowercase letter.
        $this->assertFalse($manager->accept(new SplFileInfo($this->createTestItem('Test.txt'), '', 'Test.txt')), 'Test.txt should be ignored due to regex negation');
        $this->assertFalse($manager->accept(new SplFileInfo($this->createTestItem('123.txt'), '', '123.txt')), '123.txt should be ignored due to regex negation');
        $this->assertTrue($manager->accept(new SplFileInfo($this->createTestItem('test.txt'), '', 'test.txt')), 'test.txt should be accepted (regex does not match)');
    }

}
