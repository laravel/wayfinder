@if ($parameters->isNotEmpty())
args{!! when($parameters->every->optional, '?') !!}: {
    @foreach ($parameters as $parameter)
        {{ $parameter->name }}{!! when($parameter->optional, '?') !!}: {!! $parameter->types !!}
        @if ($parameter->key)
            | { {!! $parameter->key !!}: {!! $parameter->types !!} }
        @endif,
    @endforeach
}

| [
    @foreach ($parameters as $parameter)
        {{ $parameter->safeName() }}: {!! $parameter->types !!}
        @if ($parameter->key)
            | { {!! $parameter->key !!}: {!! $parameter->types !!} }
         @endif
        {!! when(!$loop->last, ', ') !!}
    @endforeach
]

@if ($parameters->count() === 1) | {!! $parameters->first()->types !!}
    @if($parameters->first()->key) | { {!! $parameters->first()->key !!}: {!! $parameters->first()->types !!} }@endif
@endif
,
@endif
options?: RouteQueryOptions
