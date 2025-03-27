@use('Illuminate\Support\HtmlString')
/** {!! when(!str_contains($controller, '\\Closure'), "\n * @see {$controller}::{$method}") !!}
 * @see {!! $path !!}:{!! $line !!}
@foreach ($parameters as $parameter)
@if ($parameter->default !== null)
 * @param {!! $parameter->name !!} - Default: @js($parameter->default)

@endif
@endforeach
 * @route {!! $uri !!}
 */
{!! when(($export ?? true) && !$isInvokable, 'export ') !!}const {!! $method !!} = (@includeWhen($parameters->isNotEmpty(), 'wayfinder::function-arguments', $parameters)) => ({
    uri: {!! $method !!}.url({!! when($parameters->isNotEmpty(), 'args') !!}),
    method: @js($verbs->first()->actual),
})

{!! $method !!}.form = (@includeWhen($parameters->isNotEmpty(), 'wayfinder::function-arguments', $parameters)) => ({
    action: {!! $method !!}.url({!! when($parameters->isNotEmpty(), 'args') !!}){!! when($verbs->first()->formSafe !== $verbs->first()->actual, " + '?_method=" . strtoupper($verbs->first()->actual) . "'") !!},
    method: @js($verbs->first()->formSafe),
})

{!! $method !!}.definition = {
    methods: [@foreach ($verbs as $verb)@js($verb->actual){!! when(! $loop->last, ',') !!}@endforeach],
    uri: @js($uri),
}

{!! $method !!}.url = (@includeWhen($parameters->isNotEmpty(), 'wayfinder::function-arguments', $parameters)) => {
@if ($parameters->count() === 1)
    if (typeof args === 'string' || typeof args === 'number') {
        args = { {!! $parameters->first()->name !!}: args }
    }

@if ($parameters->first()->key)
    if (typeof args === 'object' && !Array.isArray(args) && @js($parameters->first()->key) in args) {
        args = { {!! $parameters->first()->name !!}: args.{!! $parameters->first()->key !!} }
    }

@endif
@endif
@if ($parameters->isNotEmpty())
    if (Array.isArray(args)) {
        args = {
@foreach ($parameters as $parameter)
            {!! $parameter->name !!}: args[{!! $loop->index !!}],
@endforeach
        }
    }

@endif
@if ($parameters->where('optional')->isNotEmpty())
    validateParameters(args, [
@foreach ($parameters->where('optional') as $parameter)
        "{!! $parameter->name !!}",
@endforeach
    ])

@endif
@if ($parameters->isNotEmpty())
    const parsedArgs = {
@foreach ($parameters as $parameter)
@if ($parameter->key)
        {!! $parameter->name !!}: {!! when($parameter->default !== null, '(') !!}typeof args{!! when($parameters->every->optional, '?') !!}.{!! $parameter->name !!} === 'object'
            ? args.{!! $parameter->name !!}.{!! $parameter->key ?? 'id' !!}
            : args{!! when($parameters->every->optional, '?') !!}.{!! $parameter->name !!}{!! when($parameter->default !== null, ') ?? ') !!}@if ($parameter->default !== null)@js($parameter->default)@endif,
@else
        {!! $parameter->name !!}: args{!! when($parameters->every->optional, '?') !!}.{!! $parameter->name !!}{!! when($parameter->default !== null, ' ?? ') !!}@if ($parameter->default !== null)@js($parameter->default)@endif,
@endif
@endforeach
    }

@endif
    return {!! $method !!}.definition.uri
@foreach ($parameters as $parameter)
            .replace(@js($parameter->placeholder), parsedArgs.{!! $parameter->name !!}{!! when($parameter->optional, '?') !!}.toString(){!! when($parameter->optional, " ?? ''") !!})
@if ($loop->last)
            .replace(/\/+$/, '')
@endif
@endforeach
}

@foreach ($verbs as $verb)
/** {!! when(!str_contains($controller, '\\Closure'), "\n * @see {$controller}::{$method}") !!}
 * @see {!! $path !!}:{!! $line !!}
 * @route {!! $uri !!}
 */
{!! $method !!}.{!! $verb->actual !!} = (@includeWhen($parameters->isNotEmpty(), 'wayfinder::function-arguments', $parameters)) => ({
    uri: {!! $method !!}.url({!! when($parameters->isNotEmpty(), 'args') !!}),
    method: @js($verb->actual),
})

/** {!! when(!str_contains($controller, '\\Closure'), "\n * @see {$controller}::{$method}") !!}
 * @see {!! $path !!}:{!! $line !!}
 * @route {!! $uri !!}
 */
// @ts-ignore
{!! $method !!}.form.{!! $verb->actual !!} = (@includeWhen($parameters->isNotEmpty(), 'wayfinder::function-arguments', $parameters)) => ({
    action: {!! $method !!}.url({!! when($parameters->isNotEmpty(), 'args') !!}){!! when($verbs->first()->formSafe !== $verbs->first()->actual, " + '?_method=" . strtoupper($verbs->first()->actual) . "'") !!},
    method: @js($verb->formSafe),
})

@endforeach
