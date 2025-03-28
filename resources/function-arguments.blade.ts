@trimDeadspace
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
        {{ $parameter->name }}: {!! $parameter->types !!}
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
query?: Record<string, string | number | boolean | string[] | null | undefined | Record<string, string | number | boolean | null | undefined>>,
@endtrimDeadspace
