@foreach ($routes as $route)
    @include('wayfinder::method', [
        ...$route,
        'method' => $route['tempMethod'],
        'docblock_method' => $route['method'],
        'shouldExport' => false,
    ])
@endforeach

/**
* Multiple routes resolve to {!! $controller !!}::{!! $original_method !!}, so this export is a
* dictionary keyed by URI rather than a callable, with the verbs prefixed where two routes share a URI.
* Call a specific route with `{!! $method !!}['<key>'](...)`, or import the route by name from your
* generated `routes/` directory.
*/
{!! when($shouldExport, 'export ') !!}const {!! $method !!} = {
@foreach ($routes as $route)
    {!! $route['key'] !!}: {!! $route['tempMethod'] !!},
@endforeach
}{{PHP_EOL}}
