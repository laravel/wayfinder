# Laravel Wayfinder

This application uses Laravel Wayfinder to generate TypeScript from its Laravel code: route and controller-action functions, form request types, model interfaces, enums, Inertia page props, broadcast channels and events, and Vite environment variables.

- Generated files live under `resources/js/wayfinder` and are imported from `@/wayfinder/...`. Never hand-edit them; change the PHP and run `php artisan wayfinder:generate`.
- Import route functions from the path matching the controller's PHP namespace (`@/wayfinder/App/Http/Controllers/PostController`), named routes from `@/wayfinder/routes/<name>`, and every type from `@/wayfinder/types`.
- Import types rather than redeclaring them. A hand-written interface for a model, page props or a form request will drift.
- Keep anything that should not reach the browser out with the `#[WayfinderIgnore]` attribute, or a `@wayfinder-ignore` comment for an array key.

When working on Wayfinder itself — generating types, wiring the Vite plugin, choosing what to leave out, or debugging missing output — invoke `wayfinder-development` for detailed rules.
