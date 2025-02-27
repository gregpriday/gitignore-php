# GitIgnore PHP

GitIgnore PHP is a lightweight yet powerful PHP library for parsing and managing gitignore‑style rules. It provides a robust engine to interpret `.gitignore` patterns—including advanced brace expansion, recursive wildcard matching, negation rules, and configurable case sensitivity. Use GitIgnore PHP to filter files in a repository based on ignore rules, integrate with file processing systems, or build custom file parsers.

---

## Features

- **Gitignore Parsing:**  
  Automatically scans your project directory for `.gitignore` files (and optionally additional ignore files) and loads their rules.

- **Advanced Glob Pattern Support:**  
  Handles standard wildcards:
  - `*` – Matches any sequence of characters (except directory separators).
  - `**` – Matches across directory boundaries.
  - `?` – Matches any single character.

- **Brace Expansion:**  
  Write compact rules such as `*.{js,css,html}` that expand to multiple patterns (`*.js`, `*.css`, and `*.html`). Nested brace expressions are also supported.

- **Negation and Rule Precedence:**  
  Supports negation rules (using `!`) and complex negation chains so that you can re‑include files that might be excluded by earlier rules. Rules are applied hierarchically based on their directory depth.

- **Directory‑only Rules:**  
  Patterns ending with a slash (e.g. `build/`) mark directories (and all their contents) as ignored.

- **Configurable Case Sensitivity:**  
  Choose between case‑sensitive or case‑insensitive matching via the constructor.

- **Multiple Ignore Files:**  
  While the default is to load `.gitignore` files, you can specify additional files (e.g. `['.ctreeignore']`) to merge extra rules.

- **Seamless Symfony Finder Integration:**  
  Designed to work in tandem with Symfony's Finder component, making it easy to filter entire file sets.

---

## Installation

Install GitIgnore PHP via Composer:

```bash
composer require gregpriday/gitignore-php
```

---

## Usage

### Basic Usage

Load a project's gitignore rules and determine whether a file should be ignored:

```php
<?php

require 'vendor/autoload.php';

use GregPriday\GitIgnore\GitIgnoreManager;
use Symfony\Component\Finder\SplFileInfo;

// Create a GitIgnoreManager instance for your project directory
$manager = new GitIgnoreManager('/path/to/your/project');

// Check a specific file using Symfony's SplFileInfo
$file = new SplFileInfo('/path/to/your/project/src/Example.php', 'src', 'src/Example.php');

if ($manager->accept($file)) {
    echo "File is not ignored.\n";
} else {
    echo "File is ignored.\n";
}
```

### Using Additional Ignore Files

By default, only `.gitignore` files are loaded. To include extra ignore files (for example, `.ctreeignore`), pass an array as the third parameter:

```php
<?php

use GregPriday\GitIgnore\GitIgnoreManager;

// Load the default .gitignore and add .ctreeignore as an extra ignore file.
$manager = new GitIgnoreManager('/path/to/your/project', true, ['.ctreeignore']);
```

### Testing Brace Expansion

The library supports compact rule definitions using brace expansion. For example:

```php
<?php

use GregPriday\GitIgnore\PatternConverter;

$converter = new PatternConverter();
$expanded = $converter->expandBraces('*.{js,css,html}');
// Expected output: ['*.js', '*.css', '*.html']
print_r($expanded);
```

### Filtering a Git Repository

Combine GitIgnore PHP with Symfony Finder to list all files that are not ignored:

```php
<?php

use GregPriday\GitIgnore\GitIgnoreManager;
use Symfony\Component\Finder\Finder;

$projectPath = '/path/to/your/git/repo';
$manager = new GitIgnoreManager($projectPath);

$finder = new Finder();
$finder->files()->in($projectPath);

$acceptedFiles = [];

foreach ($finder as $file) {
    if ($manager->accept($file)) {
        $acceptedFiles[] = $file->getRelativePathname();
    }
}

echo "Accepted files:\n";
print_r($acceptedFiles);
```

---

## API Reference

### GitIgnoreManager

- **`__construct(string $basePath, bool $caseSensitive = true, array $additionalIgnoreFiles = [])`**  
  Initializes the manager by scanning the base directory for ignore files. By default, only `.gitignore` is loaded; additional files can be specified.

- **`accept(SplFileInfo $file): bool`**  
  Determines whether the specified file should be accepted (i.e. not ignored) based on the loaded rules.

- **`matchPattern(string $pattern, string $subject): bool`**  
  Checks if the given ignore pattern matches the provided subject string.

### PatternConverter

- **`convertPatternToRegex(string $pattern): string`**  
  Converts a gitignore‑style pattern into a regular expression (without delimiters), handling escapes, wildcards, and character classes.

- **`expandBraces(string $pattern): array`**  
  Recursively expands brace expressions (e.g. `*.{js,css,html}`) into an array of individual patterns.

- **`patternToRegex(string $pattern): string`**  
  Converts a gitignore pattern into a complete regular expression with delimiters and anchors, ready for use with `preg_match()`.

---

## Code Formatting

This project uses [Laravel Pint](https://github.com/laravel/pint) to enforce a consistent coding style. To format your code, run:

```bash
composer format
```

---

## Testing

The project includes extensive test coverage using PHPUnit. To run the tests, execute:

```bash
composer test
```

or

```bash
vendor/bin/phpunit
```

---

## Contributing

Contributions are welcome! To contribute:

1. **Fork the Repository:**  
   Create your own fork and clone it locally.

2. **Create a New Branch:**  
   Develop your feature or fix on a new branch.

3. **Follow Coding Standards:**  
   Ensure your code adheres to the Laravel coding style by running `composer format` and make sure all tests pass.

4. **Submit a Pull Request:**  
   Provide a clear explanation of your changes in your pull request.

---

## License

GitIgnore PHP is released under the MIT License. See the [LICENSE](LICENSE) file for details.

---

GitIgnore PHP provides a comprehensive and flexible toolset for file filtering using gitignore‑style rules. Its advanced pattern support, combined with Symfony Finder integration, makes it ideal for both simple and complex project workflows. Happy coding!