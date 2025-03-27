@use('Illuminate\Support\HtmlString')

@foreach ($routes as $route)
@include('wayfinder::method', [
    ...$route,
    'method' => $route['tempMethod'],
    'export' => false,
])
@endforeach
{!! when(!$isInvokable, 'export ') !!}const {!! $method !!} = {
@foreach ($routes as $route)
    @js($route['uri']): {!! $route['tempMethod'] !!},
@endforeach
}{{"\n"}}
