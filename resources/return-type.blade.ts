@use('Illuminate\Support\Str')
@trimDeadspace
@isset($verb)
  RouteDefinition{!! when(Str::is('head', $verb->actual), "<'head'>") !!}
@else
  RouteDefinition{!! when(Str::is('head', $verbs->first()->actual), "<'head'>") !!}
@endisset
@endtrimDeadspace