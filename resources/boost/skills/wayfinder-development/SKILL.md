---
name: wayfinder-development
description: "ACTIVATE when working with Laravel Wayfinder, which generates TypeScript from a Laravel application: route and controller-action functions, form request types, Eloquent model interfaces, PHP enum types and constants, Inertia page props and shared data, broadcast channels and events, and Vite environment variables. Trigger when the user mentions Wayfinder, wayfinder:generate, @laravel/vite-plugin-wayfinder, config/wayfinder.php, the WayfinderIgnore attribute or a @wayfinder-ignore comment, or imports from @/wayfinder/. Also activate when a frontend needs typed URLs for Laravel routes, typed Inertia props, typed Echo channels or events, or when generated types are missing, stale or leaking data that should stay on the server. Do NOT activate for Ziggy, or for hand-written TypeScript with no Laravel backend behind it."
license: MIT
metadata:
  author: laravel
---

# Laravel Wayfinder

Wayfinder reads the Laravel application and writes TypeScript for it. The PHP is the source of truth; the TypeScript is build output.

Always verify against the docs. Wayfinder is pre-1.0 and its config keys, output paths and generated shapes change between releases. Before giving setup steps, config examples or exact syntax, call the Laravel Boost `search-docs` tool scoped to the installed `laravel/wayfinder` version, and read `config/wayfinder.php` in the project. Treat the rules below as durable patterns and `search-docs` as the source of truth for specifics.

## Generated Files Are Output

Everything under the output directory (`resources/js/wayfinder` by default) is regenerated on every build. Never edit it, never patch around it, and never commit a fix to it — change the PHP that produced it and run `php artisan wayfinder:generate`.

The same applies to types. Import `App.Models.User` from `@/wayfinder/types` rather than writing a matching interface by hand; a hand-written copy stops matching the backend the first time a cast or a column changes, and nothing fails to tell you.

```
php artisan wayfinder:generate            # regenerate
php artisan wayfinder:generate --fresh    # clear the analysis cache first
php artisan wayfinder:generate --path=resources/ts/api
```

Reach for `--fresh` when output looks stale after a PHP change. Wayfinder caches its analysis, and a stale cache is the usual cause of "my change did not appear".

For local development, install `@laravel/vite-plugin-wayfinder` and add `wayfinder()` to the Vite plugin list so generation runs on PHP changes instead of by hand.

## Import Paths

| What | Where |
| ---- | ----- |
| Controller actions | `@/wayfinder/<controller PHP namespace path>` |
| Named routes | `@/wayfinder/routes/<name>` |
| All types | `@/wayfinder/types` |
| Enum constants | `@/wayfinder/App/Enums/<Enum>` |
| Broadcast channels | `@/wayfinder/broadcast-channels` |

Enums are split deliberately: the type lives in `types.d.ts` and the runtime constants in their own file. Use the type to constrain a value and the constant to compare one — never a bare string literal, which survives a rename of the PHP case.

## Calling Routes

A route function returns `{ url, method }`. `.url()` returns the string alone.

```typescript
PostController.show({ post: 1 });        // { url: '/posts/1', method: 'get' }
PostController.show.url({ post: 1 });    // '/posts/1'
PostController.index({ query: { page: 2 } });
PostController.index({ mergeQuery: { page: 3 } });
```

Method variants (`.get()`, `.patch()`, `.head()`) are there when a route answers to more than one verb. Spread `.form()` into an HTML `<form>`: it returns `{ action, method }` with Laravel's `_method` spoofing already in the query string, because forms only speak GET and POST.

```tsx
<form {...PostController.update.form({ post: post.id })}>
```

## Leaving Things Out

Wayfinder can see more of the application than belongs in a browser bundle. Decide this deliberately for anything touching tokens, internal endpoints, admin-only relations or personal data.

Use the `#[WayfinderIgnore]` attribute on a controller class or single action, a model, an enum or one of its cases or methods, a broadcast event or channel, and a model's accessors and relations. Use a `@wayfinder-ignore` comment where an attribute cannot go — an array key in Inertia props, `toArray()`, `broadcastWith()`, or form request rules.

```php
#[WayfinderIgnore]
public function impersonate() { /* not generated */ }
```

```php
return Inertia::render('Profile', [
    'name' => $user->name,
    'apiToken' => $user->api_token, // @wayfinder-ignore
]);
```

Facts worth holding on to:

- Dropping an action drops everything hanging off it: route helper, form variant, page type, request type. Dropping a model drops relations pointing at it.
- A marker hides a member from its own type only. If something else hands the same value out under its own key, mark that too.
- Nothing is generated for a trait, so mark the members inside it, not the trait.
- Model `$hidden` and `#[Hidden]` are already honored; no extra marker needed for a plain hidden column.
- The key is removed, not emptied, so frontend code still reading it fails to compile. That is the point.

### Conditional Markers

`unless: <condition>` keeps a declaration only while the condition holds; `when: <condition>` drops it while the condition holds. A condition is a config key or a `[class, method]` callable — nothing else counts, so `#[WayfinderIgnore(true)]` is just an unconditional marker.

```php
#[WayfinderIgnore(unless: 'services.fake_source_provider')]
case GitFake = 'gitfake';
```

Conditions are answered at generation time by the environment generating them, and only the condition is cached, never its answer. A condition that cannot be read drops the declaration whichever argument was used — so a typo surfaces as a type error rather than shipping the member. Both arguments together only ever add hiding; `unless` keeping something cannot override `when` dropping it.

Plan for the consequence: the production build genuinely lacks the member, so code reading it must sit somewhere the production build never typechecks.

To honor a marker from a package you do not control, add it to `generate.ignore.attributes` (or a comment tag to `generate.ignore.tags`) in `config/wayfinder.php`. Your own attributes need no registration if they implement `Laravel\Surveyor\Contracts\Ignored`.

## Configuration

`config/wayfinder.php` controls what gets generated. Two options need care:

- `generate.enum_methods` (default `false`) calls every no-argument method on every enum case at generation time and bakes the results into a constant. Anything depending on locale, database or environment is frozen as it was when the command ran — a `label()` returning `__('post.draft')` ships one translation. Turn it on only when the methods are pure.
- `generate.route.ignore.names` and `.urls` are the right place for whole families of routes from packages that expose their own endpoints, rather than marking each one.

## Common Pitfalls

- Editing generated files. The next build overwrites the change.
- Redeclaring a model, page prop or request type by hand instead of importing it from `@/wayfinder/types`.
- Comparing against a string literal where an enum constant exists. A renamed PHP case leaves the literal compiling and wrong.
- Forgetting `--fresh` after a PHP change that output does not reflect.
- Assuming a marker on a trait hides its members. It does not.
- Leaving a token, internal endpoint or admin-only relation in the generated output because nothing forced the decision.

## Verification

1. Confirm no file under the output directory has been edited by hand.
2. Confirm the frontend imports types from `@/wayfinder/types` rather than declaring its own.
3. Confirm nothing sensitive appears in the generated output — search it for token, secret and internal-only field names.
4. Confirm `php artisan wayfinder:generate` runs clean and leaves no diff after a fresh run.
