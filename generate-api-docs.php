<?php declare(strict_types=1);

require_once 'vendor/autoload.php';

$classes = [
    'KDuma\BinaryTools\BinaryString',
    'KDuma\BinaryTools\BinaryWriter',
    'KDuma\BinaryTools\BinaryReader',
    'KDuma\BinaryTools\IntType',
    'KDuma\BinaryTools\Terminator',
];

$markdown = "## Binary Tools for PHP - API Reference\n\n";
$markdown .= "This documentation is auto-generated from the source code.\n\n";

// Generate Table of Contents
$markdown .= "### Table of Contents\n\n";
$toc = [];

// First pass to build TOC
foreach ($classes as $className) {
    $reflection = new \ReflectionClass($className);
    $shortName = $reflection->getShortName();

    // Skip enums in main TOC - they'll be in the Enums section
    if ($reflection->isEnum()) {
        continue;
    }

    $toc[] = "* [`\\$className`](#$shortName)";

    // Add properties to TOC
    $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
    foreach ($properties as $property) {
        $propName = $property->getName();
        $toc[] = "  * [`$shortName::\$$propName`](#$shortName$propName)";
    }

    // Add methods to TOC
    $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
    $publicMethods = array_filter($methods, fn ($method) => !$method->isConstructor() && !$method->isDestructor());
    foreach ($publicMethods as $method) {
        $methodName = $method->getName();
        $paramCount = count($method->getParameters());
        $paramSuffix = $paramCount > 0 ? '(...)' : '()';
        $toc[] = "  * [`$shortName::$methodName$paramSuffix`](#$shortName$methodName)";
    }
}

// Add enum section if any
$hasEnums = false;
foreach ($classes as $className) {
    $reflection = new \ReflectionClass($className);
    if ($reflection->isEnum()) {
        if (!$hasEnums) {
            $toc[] = "* [Enums](#enums)";
            $hasEnums = true;
        }
        $shortName = $reflection->getShortName();
        $toc[] = "  * [`\\$className`](#$shortName)";
    }
}

$markdown .= implode("\n", $toc) . "\n\n";

foreach ($classes as $className) {
    $reflection = new \ReflectionClass($className);

    // Skip enums in main section - they'll be handled in the Enums section
    if ($reflection->isEnum()) {
        continue;
    }

    $markdown .= "### " . $reflection->getShortName() . "\n\n";

    // Class description from docblock
    $docComment = $reflection->getDocComment();
    if ($docComment) {
        $lines = explode("\n", $docComment);
        $description = '';
        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, '/**') || str_starts_with($line, '*/') || str_starts_with($line, '*')) {
                $line = ltrim($line, '/*');
                $line = trim($line);
                if (!empty($line) && !str_starts_with($line, '@')) {
                    $description .= $line . " ";
                }
            }
        }
        if (!empty($description)) {
            $markdown .= trim($description) . "\n\n";
        }
    }

    // Namespace
    $markdown .= "**Namespace:** `" . $reflection->getNamespaceName() . "`\n\n";

    // Class type
    if ($reflection->isEnum()) {
        $markdown .= "**Type:** Enum\n\n";
    } elseif ($reflection->isFinal()) {
        $markdown .= "**Type:** Final Class\n\n";
    } else {
        $markdown .= "**Type:** Class\n\n";
    }

    // Public constants (for enums)
    $constants = $reflection->getConstants();
    if (!empty($constants) && $reflection->isEnum()) {
        $markdown .= "### Cases\n\n";
        foreach ($constants as $name => $value) {
            $markdown .= "- `$name`\n";
        }
        $markdown .= "\n";
    }

    // Public properties
    $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
    if (!empty($properties)) {
        $markdown .= "#### Properties\n\n";
        foreach ($properties as $property) {
            $name = $property->getName();
            $type = $property->getType() ? $property->getType()->__toString() : 'mixed';
            $shortName = $reflection->getShortName();

            $markdown .= "#### `\$$name`\n\n";
            $markdown .= "```php\n";
            $markdown .= "$type \$$name\n";
            $markdown .= "```\n\n";

            // Property description
            $propDoc = $property->getDocComment();
            if ($propDoc) {
                $lines = explode("\n", $propDoc);
                $description = '';
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (str_starts_with($line, '/**') || str_starts_with($line, '*/') || str_starts_with($line, '*')) {
                        $line = ltrim($line, '/*');
                        $line = trim($line);
                        if (!empty($line) && !str_starts_with($line, '@')) {
                            $description .= $line . " ";
                        }
                    }
                }
                if (!empty($description)) {
                    $markdown .= trim($description) . "\n\n";
                }
            }

            $markdown .= "--------------------\n\n";
        }
    }

    // Public methods
    $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
    $publicMethods = array_filter($methods, fn ($method) => !$method->isConstructor() && !$method->isDestructor());

    if (!empty($publicMethods)) {
        $markdown .= "#### Methods\n\n";

        foreach ($publicMethods as $method) {
            $name = $method->getName();
            $params = [];
            $paramInfo = [];

            foreach ($method->getParameters() as $param) {
                $paramType = $param->getType() ? $param->getType()->__toString() : 'mixed';
                $paramName = '$' . $param->getName();
                $paramStr = "$paramType $paramName";

                if ($param->isDefaultValueAvailable()) {
                    $default = $param->getDefaultValue();
                    if (is_null($default)) {
                        $paramStr .= ' = null';
                    } elseif (is_bool($default)) {
                        $paramStr .= ' = ' . ($default ? 'true' : 'false');
                    } elseif (is_string($default)) {
                        $paramStr .= ' = "' . $default . '"';
                    } else {
                        $paramStr .= ' = ' . $default;
                    }
                }

                $params[] = $paramStr;
                $paramInfo[] = [
                    'name' => $param->getName(),
                    'type' => $paramType,
                    'optional' => $param->isDefaultValueAvailable()
                ];
            }

            $returnType = $method->getReturnType() ? $method->getReturnType()->__toString() : 'mixed';

            $paramSuffix = count($method->getParameters()) > 0 ? '(...)' : '()';
            $markdown .= "##### $name$paramSuffix\n\n";
            $markdown .= "```php\n";

            // Format method signature with full namespace
            $fullClassName = '\\' . $reflection->getName();
            $fullReturnType = $returnType;
            if (str_contains($returnType, 'KDuma\\BinaryTools\\') && !str_starts_with($returnType, '\\')) {
                $fullReturnType = '\\' . $returnType;
            }

            if (count($method->getParameters()) > 0) {
                // Multiline format for methods with parameters
                $markdown .= "$fullClassName::$name(\n";
                $formattedParams = [];
                foreach ($params as $param) {
                    // Add namespace prefix to types
                    $param = preg_replace('/\bKDuma\\\\BinaryTools\\\\([A-Za-z]+)/', '\\\\KDuma\\\\BinaryTools\\\\$1', $param);
                    $formattedParams[] = "    $param";
                }
                $markdown .= implode(",\n", $formattedParams) . "\n";
                $markdown .= "): $fullReturnType\n";
            } else {
                // Single line for methods without parameters
                $markdown .= "$fullClassName::$name(): $fullReturnType\n";
            }

            $markdown .= "```\n\n";

            // Method description
            $methodDoc = $method->getDocComment();
            $description = '';
            $docParams = [];
            $returnDoc = '';

            if ($methodDoc) {
                $lines = explode("\n", $methodDoc);

                foreach ($lines as $line) {
                    $line = trim($line);
                    if (str_starts_with($line, '/**') || str_starts_with($line, '*/')) {
                        continue;
                    }

                    $line = ltrim($line, '*');
                    $line = trim($line);

                    if (str_starts_with($line, '@param')) {
                        $parts = explode(' ', $line, 4);
                        if (count($parts) >= 3) {
                            $paramName = trim($parts[2], '$');
                            $paramDesc = isset($parts[3]) ? $parts[3] : '';
                            $docParams[$paramName] = $paramDesc;
                        }
                    } elseif (str_starts_with($line, '@return')) {
                        $returnDoc = str_replace('@return', '', $line);
                        $returnDoc = trim($returnDoc);
                    } elseif (str_starts_with($line, '@throws')) {
                        $description .= "\n\n**Throws:** " . str_replace('@throws', '', $line);
                    } elseif (!empty($line) && !str_starts_with($line, '@')) {
                        $description .= $line . " ";
                    }
                }
            }

            if (!empty($description)) {
                $markdown .= trim($description) . "\n\n";
            }

            // Parameter table
            if (!empty($paramInfo)) {
                $markdown .= "| Param | Type | Description |\n";
                $markdown .= "| ----- | ---- | ----------- |\n";

                foreach ($paramInfo as $info) {
                    $paramName = "**`{$info['name']}`**";

                    // Format type with namespace prefix and optional indicator
                    $paramType = $info['type'];
                    if (str_contains($paramType, 'KDuma\\BinaryTools\\') && !str_starts_with($paramType, '\\')) {
                        $paramType = '\\' . $paramType;
                    }

                    // Escape pipe characters for markdown table
                    $escapedParamType = str_replace('|', '\\|', $paramType);
                    $typeColumn = "<code>$escapedParamType</code>";

                    if ($info['optional']) {
                        $typeColumn .= ' (optional)';
                    }

                    // Get description from docblock
                    $description = isset($docParams[$info['name']]) ? $docParams[$info['name']] : '';

                    $markdown .= "| $paramName | $typeColumn | $description |\n";
                }
                $markdown .= "\n";
            }

            // Return type
            $escapedReturnType = str_replace('|', '\\|', $fullReturnType);
            if (!empty($returnDoc)) {
                $markdown .= "**Returns:** <code>$escapedReturnType</code> - $returnDoc\n\n";
            } else {
                $markdown .= "**Returns:** <code>$escapedReturnType</code>\n\n";
            }

            $markdown .= "--------------------\n\n";
        }
    }

    $markdown .= "---\n\n";
}

// Add Enums section
$enumClasses = array_filter($classes, function ($className) {
    $reflection = new \ReflectionClass($className);
    return $reflection->isEnum();
});

if (!empty($enumClasses)) {
    $markdown .= "### Enums\n\n";

    foreach ($enumClasses as $enumClass) {
        $reflection = new \ReflectionClass($enumClass);
        $shortName = $reflection->getShortName();

        $markdown .= "#### $shortName\n\n";

        // Get enum description from docblock
        $docComment = $reflection->getDocComment();
        if ($docComment) {
            $lines = explode("\n", $docComment);
            $description = '';
            foreach ($lines as $line) {
                $line = trim($line);
                if (str_starts_with($line, '/**') || str_starts_with($line, '*/') || str_starts_with($line, '*')) {
                    $line = ltrim($line, '/*');
                    $line = trim($line);
                    if (!empty($line) && !str_starts_with($line, '@')) {
                        $description .= $line . " ";
                    }
                }
            }
            if (!empty($description)) {
                $markdown .= trim($description) . "\n\n";
            }
        }

        // Namespace
        $markdown .= "**Namespace:** `\\{$reflection->getName()}`\n\n";

        // Enum cases table
        $constants = $reflection->getConstants();
        if (!empty($constants)) {
            $markdown .= "| Members | Value | Description |\n";
            $markdown .= "| ------- | ----- | ----------- |\n";

            foreach ($constants as $name => $value) {
                $memberName = "**`$name`**";

                // For pure enums (not backed), just show the case name
                // For backed enums, we'd need to use the enum's backing value
                $caseValue = "<code>'$name'</code>";

                // Try to get more specific value info if it's a backed enum
                try {
                    if (method_exists($reflection, 'getBackingType')) {
                        $backingType = $reflection->getBackingType();
                        if ($backingType) {
                            // This is a backed enum, but we can't easily get the backing value
                            // without instantiating the enum case
                            $caseValue = "<code>'$name'</code>";
                        }
                    }
                } catch (\Exception $e) {
                    // Fallback to case name
                }

                // Try to get description from case docblock by reading the source file
                $description = '';
                try {
                    $filename = $reflection->getFileName();
                    if ($filename) {
                        $source = file_get_contents($filename);
                        $lines = explode("\n", $source);

                        // Look for the case definition and preceding docblock
                        for ($i = 0; $i < count($lines); $i++) {
                            if (preg_match('/case\s+' . preg_quote($name) . '\s*;/', $lines[$i])) {
                                // Found the case, look back for docblock
                                for ($j = $i - 1; $j >= 0; $j--) {
                                    $line = trim($lines[$j]);
                                    if (str_starts_with($line, '/**')) {
                                        // Found docblock start, extract description
                                        $docLine = str_replace('/**', '', $line);
                                        $docLine = str_replace('*/', '', $docLine);
                                        $description = trim($docLine);
                                        break;
                                    }
                                    if (!str_starts_with($line, '*') && !empty($line)) {
                                        // Found non-docblock content, stop looking
                                        break;
                                    }
                                }
                                break;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Fallback to empty description
                }

                $markdown .= "| $memberName | $caseValue | $description |\n";
            }
            $markdown .= "\n";
        }

        $markdown .= "--------------------\n\n";
    }
}

echo $markdown;
