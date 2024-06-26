<?php
namespace Apie\ServiceProviderGenerator;

use LogicException;
use Symfony\Component\Yaml\Tag\TaggedValue;
use Symfony\Component\Yaml\Yaml;

final class ServiceProviderGenerator
{
    public function generateClass(
        string $className,
        string $yamlInputFile
    ): string {
        $contents = Yaml::parseFile($yamlInputFile, Yaml::PARSE_CUSTOM_TAGS);
        $registerMethodBody = '';
        $tags = [];
        foreach (($contents['services'] ?? []) as $serviceId => $serviceDefinition) {
            if ($serviceDefinition['synthetic'] ?? false) {
                continue;
            }
            $registerMethodBody .= $this->generateServiceDefinition($serviceDefinition ?? [], $serviceId) . PHP_EOL;
        }

        $namespace = substr($className, 0, strrpos($className , '\\'));
        $classWithoutNs = substr($className, strrpos($className , '\\') + 1);

        $sourceCode = '<?php' . PHP_EOL . 'namespace ' . $namespace . ';' . PHP_EOL . PHP_EOL;
        $sourceCode .= 'use Apie\ServiceProviderGenerator\UseGeneratedMethods;' . PHP_EOL;
        $sourceCode .= 'use Illuminate\Support\ServiceProvider;' . PHP_EOL;
        $sourceCode .= PHP_EOL;
        $sourceCode .= '/**' . PHP_EOL;
        $sourceCode .= ' * This file is generated with apie/service-provider-generator from file: ' . basename($yamlInputFile) . PHP_EOL;
        $sourceCode .= ' * @codeCoverageIgnore' . PHP_EOL;
        $sourceCode .= ' */' . PHP_EOL;
        $sourceCode .= 'class ' . $classWithoutNs . ' extends ServiceProvider' . PHP_EOL . '{' . PHP_EOL;
        $sourceCode .= '    use UseGeneratedMethods;' . PHP_EOL . PHP_EOL;
        $sourceCode .= '    public function register()' . PHP_EOL;
        $sourceCode .= '    {' . PHP_EOL;
        $sourceCode .= CodeUtils::indent($registerMethodBody, 8) . PHP_EOL;
        $sourceCode .= '    }' . PHP_EOL;
        $sourceCode .= '}' . PHP_EOL;
        return $sourceCode;
    }

    public function createCodeForArgumentList(array $argumentList, int $indentation = 0): string
    {
        $indentString = str_repeat(' ', $indentation);
        $arguments = [];
        foreach ($argumentList as $key => $argument) {
            $prefix = is_int($key) ? '' : ltrim($key, '$');
            if ($prefix) {
                $prefix .= ': ';
            }
            $arguments[] = $indentString . $prefix . $this->createCodeForArgument($argument);
        }
        return implode(',' . PHP_EOL, $arguments) . PHP_EOL;
    }

    public function generateServiceDefinition(array $serviceDefinition, string $serviceId)
    {
        if ($serviceDefinition['alias'] ?? null) {
            return $this->generateAlias($serviceId, $serviceDefinition['alias']) . PHP_EOL;
        }
        $factory = $serviceDefinition['factory'] ?? null;
        $className = $serviceDefinition['class'] ?? $serviceId;
        $code = empty($serviceDefinition['calls']) ? 'return ' : '$service = ';
        switch (get_debug_type($factory)) {
            case 'array':
                if (null === $factory[0]) {
                    $code .= '\\' . $className . '::';
                } else if(str_starts_with($factory[0], '@')) {
                    $code .= '$this->app->make(' . CodeUtils::renderString(substr($factory[0], 1)) . ')->';
                } else {
                    $code .= '\\' . $factory[0] . '::';
                }
                $code .= $factory[1] . '(' . PHP_EOL;
                $code .= $this->createCodeForArgumentList($serviceDefinition['arguments'] ?? [], 4);
                $code .= ');' . PHP_EOL;
                $code = CodeUtils::indent($code, 8);
                break;
            case 'string':
                $codeArguments = $serviceDefinition['arguments'] ?? [];
                $code .= 'call_user_func(' . PHP_EOL;
                $code .= '    ' . CodeUtils::renderString($factory) . (empty($codeArguments) ? '' : ',') . PHP_EOL;
                $code .= $this->createCodeForArgumentList($codeArguments, 4);
                $code .= ');' . PHP_EOL;
                $code = CodeUtils::indent($code, 8);
                break;
            case 'null':
                $argumentsCode = $this->createCodeForArgumentList($serviceDefinition['arguments'] ?? [], 4);
                $code = CodeUtils::indent(
                    $code . 'new \\' . $className . '(' . PHP_EOL
                        . $argumentsCode
                        . ');', 
                    8
                );
                    
                break;
            default:
                throw new LogicException('Unknown factory type: ' . get_debug_type($factory));
        }

        foreach (($serviceDefinition['calls'] ?? []) as $methodDefinition)
        {
            $methodArguments = reset($methodDefinition);
            $methodName = key($methodDefinition);
            $methodCallCode = '$service->' . $methodName . '('. PHP_EOL . $this->createCodeForArgumentList($methodArguments ?? [], 4) . ');';
            $code .= PHP_EOL . CodeUtils::indent($methodCallCode, 8);         
        }
        
        $tagCode = '';
        if ($serviceDefinition['tags'] ?? null) {
            $tagCode .= PHP_EOL . '\\' . TagMap::class . '::register(' . PHP_EOL;
            $tagCode .= '    $this->app,' . PHP_EOL;
            $tagCode .= '    ' . CodeUtils::renderString($serviceId) . ',' . PHP_EOL;
            $tagCode .= CodeUtils::indent(var_export($serviceDefinition['tags'], true), 4) . PHP_EOL;
            $tagCode .= ');';
            foreach ($serviceDefinition['tags'] as $tag) {
                if (is_string($tag)) {
                    $tagCode .= PHP_EOL
                        . '$this->app->tag(['
                        . CodeUtils::renderString($serviceId)
                        . '], '
                        . CodeUtils::renderString($tag)
                        . ');';
                } elseif (is_array($tag)) {
                    if (isset($tag['name'])) {
                        $tagCode .= PHP_EOL
                            . '$this->app->tag(['
                            . CodeUtils::renderString($serviceId)
                            . '], '
                            . CodeUtils::renderString($tag['name'])
                            . ');';
                    }
                }
            }
        }

        if (!empty($serviceDefinition['calls'])) {
            $code .= PHP_EOL . PHP_EOL . '        return $service;';
        }
        if (!empty($serviceDefinition['deprecated'])) {
            $code = '        @trigger_error(' . CodeUtils::renderString(str_replace('%service_id%', $serviceId, $serviceDefinition['deprecated'])) . ', E_USER_DEPRECATED);' . PHP_EOL . $code;
        }

        $method = ($serviceDefinition['shared'] ?? false) ? 'bind' : 'singleton';
        return '$this->app->' . $method . '(' . PHP_EOL
            . '    ' . CodeUtils::renderString($serviceId) . ',' . PHP_EOL
            . '    function ($app) {' . PHP_EOL
            . $code . PHP_EOL
            . '    }' . PHP_EOL
            . ');' . $tagCode;
    }

    private function toTag(TaggedValue $tag): string
    {
        $value = $tag->getValue();
        if (is_string($value)) {
            return $value;
        }
        return $value['tag'] ?? '';
    }

    public function createTagCode(TaggedValue $tag): string
    {
        switch ($tag->getTag()) {
            case 'tagged':
            case 'tagged_iterator':
                return '$this->getTaggedServicesIterator(' . CodeUtils::renderString($this->toTag($tag)) . ')';
            case 'tagged_locator':
                return '$this->getTaggedServicesServiceLocator(' . CodeUtils::renderString($this->toTag($tag)) . ')';
        }
        throw new LogicException('Unknown tag: ' . $tag->getTag());
    }

    public function createCodeForArgument(mixed $argument): string {
        if ($argument instanceof TaggedValue) {
            return $this->createTagCode($argument);
        }
        if (!is_string($argument)) {
            // TODO parse arrays recursively, fix indenting multiline
            return var_export($argument, true);
        }
        if ($argument === '@service_container') {
            return '$app';
        }
        if (str_starts_with($argument, '@?')) {
            $targetServiceId = CodeUtils::renderString(substr($argument, 2));
            return '$app->bound(' . $targetServiceId . ') ? $app->make(' . $targetServiceId . ') : null';
        }
        if (str_starts_with($argument, '@') && !str_starts_with($argument, '@@')) {
            return '$app->make(' . CodeUtils::renderString(substr($argument, 1)) . ')';
        }
        if (str_starts_with($argument, '@@')) {
            $argument = substr($argument, 1);
        }
        if (str_contains($argument, '%')) {
            return '$this->parseArgument(' . CodeUtils::renderString($argument) . ')';
        }
        return CodeUtils::renderString($argument);
    }

    public function generateAlias(string $interfaceClass, string $actualClass): string
    {
        return '$this->app->bind(' . CodeUtils::renderString($interfaceClass) .  ', ' . CodeUtils::renderString($actualClass) . ');';
    }
}