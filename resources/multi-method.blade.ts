@foreach ($routes as $route)
    @include('wayfinder::method', [
        ...$route,
        'method' => $route['tempMethod'],
        'docblock_method' => $route['method'],
        'shouldExport' => false,
    ])
@endforeach

{!! when($shouldExport, 'export ') !!}const {!! $method !!} = {
@foreach ($routes as $route)
    {!! $route['uri'] !!}: {!! $route['tempMethod'] !!},
@endforeach
}{{PHP_EOL}}
