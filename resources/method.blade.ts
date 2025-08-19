@include('wayfinder::docblock')
{!! when($shouldExport, 'export ') !!}const {!! $method !!} = (@include('wayfinder::function-arguments')): RouteDefinition<@js($verbs->first()->actual)> => ({
    url: {!! $method !!}.url({!! when($parameters->isNotEmpty(), 'args, ') !!}options),
    method: @js($verbs->first()->actual),
})

{!! $method !!}.definition = {
    methods: {!! $verbs->pluck('actual')->toJson() !!},
    url: {!! $uri !!},
} satisfies RouteDefinition<{!! $verbs->pluck('actual')->toJson() !!}>

@include('wayfinder::docblock')
{!! $method !!}.url = (@include('wayfinder::function-arguments')) => {
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

    args = applyUrlDefaults(args)
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

    return {!! $method !!}.definition.url
@foreach ($parameters as $parameter)
            .replace(@js($parameter->placeholder), parsedArgs.{!! $parameter->name !!}{!! when($parameter->optional, '?') !!}.toString(){!! when($parameter->optional, " ?? ''") !!})
    @if ($loop->last)
            .replace(/\/+$/, '')
    @endif
@endforeach + queryParams(options)
}

@foreach ($verbs as $verb)
@include('wayfinder::docblock')
{!! $method !!}.{!! $verb->actual !!} = (@include('wayfinder::function-arguments')): RouteDefinition<@js($verb->actual)> => ({
    url: {!! $method !!}.url({!! when($parameters->isNotEmpty(), 'args, ') !!}options),
    method: @js($verb->actual),
})
@endforeach

@if ($withForm)
    @include('wayfinder::docblock')
    const {!! $method !!}Form = (@include('wayfinder::function-arguments')): RouteFormDefinition<@js($verbs->first()->formSafe)> => ({
        action: {!! $method !!}.url(
            {!! when($parameters->isNotEmpty(), 'args, ') !!}
            @if ($verbs->first()->formSafe === $verbs->first()->actual)
                options
            @else
                {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: @js(strtoupper($verbs->first()->actual)),
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }
            @endif
        ),
        method: @js($verbs->first()->formSafe),
    })

    @foreach ($verbs as $verb)
        @include('wayfinder::docblock')
        {!! $method !!}Form.{!! $verb->actual !!} = (@include('wayfinder::function-arguments')): RouteFormDefinition<@js($verb->formSafe)> => ({
            action: {!! $method !!}.url(
                {!! when($parameters->isNotEmpty(), 'args, ') !!}
                @if ($verb->formSafe === $verb->actual)
                options
                @else
                    {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: @js(strtoupper($verb->actual)),
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }
                @endif
            ),
            method: @js($verb->formSafe),
        })
    @endforeach

    {!! $method !!}.form = {!! $method !!}Form
@endif
