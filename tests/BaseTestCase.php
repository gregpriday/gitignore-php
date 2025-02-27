<?php

namespace GregPriday\GitIgnore\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * BaseTestCase
 *
 * Provides common scaffolding for tests, including temporary directory setup,
 * cleanup, and helper methods for creating test files and directories.
 */
abstract class BaseTestCase extends TestCase
{
    /**
     * The temporary directory used for testing.
     */
    protected string $tempDir;

    /**
     * Set up a unique temporary directory before each test.
     *
     * @throws RuntimeException if the directory cannot be created.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().'/gitignore_test_'.uniqid();
        if (! mkdir($this->tempDir, 0777, true) && ! is_dir($this->tempDir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $this->tempDir));
        }
    }

    /**
     * Clean up the temporary directory after each test.
     */
    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    /**
     * Recursively remove a directory and all of its contents.
     *
     * @param  string  $dir  The directory to remove.
     */
    protected function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }

    /**
     * Create a test file or directory within the temporary directory.
     *
     * @param  string  $relativePath  The path relative to the temporary directory.
     * @param  string  $content  The content to write (ignored if creating a directory).
     * @param  bool  $isDir  Whether to create a directory (true) or a file (false).
     * @return string The full path of the created item.
     */
    protected function createTestItem(string $relativePath, string $content = '', bool $isDir = false): string
    {
        $fullPath = $this->tempDir.'/'.$relativePath;
        $dir = $isDir ? $fullPath : dirname($fullPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (! $isDir) {
            file_put_contents($fullPath, $content);
        }

        return $fullPath;
    }
}
