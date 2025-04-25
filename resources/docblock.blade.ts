/**{!! when(!str_contains($controller, '\\Closure'), PHP_EOL . " * @see {$controller}::" . ($isInvokable ? '__invoke' : $docblock_method ?? $method)) !!}
 * @see {!! str_replace(DIRECTORY_SEPARATOR, '/', $path) !!}:{!! $line !!}
@foreach ($parameters as $parameter)
@if ($parameter->default !== null)
 * @param {!! $parameter->name !!} - Default: @js($parameter->default)

@endif
@endforeach
 * @route {!! $uri !!}
 */
