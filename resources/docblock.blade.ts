/** {!! when(!str_contains($controller, '\\Closure'), "\n * @see {$controller}::{$method}") !!}
 * @see {!! $path !!}:{!! $line !!}
@foreach ($parameters as $parameter)
@if ($parameter->default !== null)
 * @param {!! $parameter->name !!} - Default: @js($parameter->default)

@endif
@endforeach
 * @route {!! $uri !!}
 */
