@foreach ($routes as $route)
    @include('wayfinder::method', [
        ...$route,
        'method' => $route['tempMethod'],
        'docblock_method' => $route['method'],
        'export' => false,
    ])
@endforeach

{!! when(!$isInvokable, 'export ') !!}const {!! $method !!} = {
@foreach ($routes as $route)
    @js($route['uri']): {!! $route['tempMethod'] !!},
@endforeach
}{{PHP_EOL}}
