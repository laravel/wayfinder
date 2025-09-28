<p align="center"><img src="./art/logo.svg" width="50%" alt="Laravel Wayfinder Logo"></p>

## Introduction

Laravel Wayfinder bridges your Laravel backend and TypeScript frontend with zero friction. It automatically generates fully-typed, importable TypeScript functions for your controllers and routes — so you can call your Laravel endpoints directly in your client code just like any other function. No more hardcoding URLs, guessing route parameters, or syncing backend changes manually.

> [!IMPORTANT]
> Wayfinder is currently in Beta, the API is subject to change prior to the v1.0.0 release. All notable changes will be documented in the [changelog](./CHANGELOG.md).

## Installation

To get started, install Wayfinder via the Composer package manager:

```
composer require laravel/wayfinder
```

Next, install the [Wayfinder Vite plugin](https://github.com/laravel/vite-plugin-wayfinder) to ensure that your routes are generated during Vite's build step and also whenever your files change while running the Vite's dev server.

First, install the plugin via NPM:

```
npm i -D @laravel/vite-plugin-wayfinder
```

Then, update your application's `vite.config.js` file to watch for changes to your application's routes and controllers:

```ts
import { wayfinder } from "@laravel/vite-plugin-wayfinder";

export default defineConfig({
    plugins: [
        wayfinder(),
        // ...
    ],
});
```

You can read about all of the plugin's configuration options in the [documentation](https://github.com/laravel/vite-plugin-wayfinder).

## Generating TypeScript Definitions

The `wayfinder:generate` command can be used to generate TypeScript definitions for your routes and controller methods:

```
php artisan wayfinder:generate
```

By default, Wayfinder generates files in three directories (`wayfinder`, `actions`, and `routes`) within `resources/js`, but you can configure the base path:

```
php artisan wayfinder:generate --path=resources/js/wayfinder
```

Use the `--with-vendor-routes` option to include TypeScript definitions for vendor routes as well:

```
php artisan wayfinder:generate --with-vendor-routes
```

The `--skip-actions` and `--skip-routes` options may be used to skip TypeScript definition generation for controller methods or routes, respectively:

```
php artisan wayfinder:generate --skip-actions
php artisan wayfinder:generate --skip-routes
```

You can safely `.gitignore` the `wayfinder`, `actions`, and `routes` directories as they are completely re-generated on every build.

## Usage

Wayfinder functions return an object that contains the resolved URL and default HTTP method:

```ts
import { show } from "@/actions/App/Http/Controllers/PostController";

show(1); // { url: "/posts/1", method: "get" }
```

If you just need the URL, or would like to choose a method from the HTTP methods defined on the server, you can invoke additional methods on the Wayfinder generated function:

```ts
import { show } from "@/actions/App/Http/Controllers/PostController";

show.url(1); // "/posts/1"
show.head(1); // { url: "/posts/1", method: "head" }
```

Wayfinder functions accept a variety of shapes for their arguments:

```ts
import { show, update } from "@/actions/App/Http/Controllers/PostController";

// Single parameter action...
show(1);
show({ id: 1 });

// Multiple parameter action...
update([1, 2]);
update({ post: 1, author: 2 });
update({ post: { id: 1 }, author: { id: 2 } });
```

> [!NOTE]
> If you are using a JavaScript [reserved word](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Lexical_grammar#reserved_words) such as `delete` or `import`, as a method in your controller, Wayfinder will rename it to `[method name]Method` (`deleteMethod`, `importMethod`) when generating its functions. This is because these words are not allowed as variable declarations in JavaScript.

If you've specified a key for the parameter binding, Wayfinder will detect this and allow you to pass the value in as a property on an object:

```ts
import { show } from "@/actions/App/Http/Controllers/PostController";

// Route is /posts/{post:slug}...
show("my-new-post");
show({ slug: "my-new-post" });
```

### Invokable Controllers

If your controller is an invokable controller, you may simply invoke the imported Wayfinder function directly:

```ts
import StorePostController from "@/actions/App/Http/Controllers/StorePostController";

StorePostController();
```

### Importing Controllers

You may also import the Wayfinder generated controller definition and invoke its individual methods on the imported object:

```ts
import PostController from "@/actions/App/Http/Controllers/PostController";

PostController.show(1);
```

> [!NOTE]
> In the example above, importing the entire controller prevents the `PostController` from being tree-shaken, so all `PostController` actions will be included in your final bundle.

### Importing Named Routes

Wayfinder can also generate methods for your application's named routes as well:

```ts
import { show } from "@/routes/post";

// Named route is `post.show`...
show(1); // { url: "/posts/1", method: "get" }
```

### Conventional Forms

If your application uses conventional HTML form submissions, Wayfinder can help you out there as well. First, opt into form variants when generating your TypeScript definitions:

```shell
php artisan wayfinder:generate --with-form
```

Then, you can use the `.form` variant to generate `<form>` object attributes automatically:

```tsx
import { store, update } from "@/actions/App/Http/Controllers/PostController";

const Page = () => (
    <form {...store.form()}>
        {/* <form action="/posts" method="post"> */}
        {/* ... */}
    </form>
);

const Page = () => (
    <form {...update.form(1)}>
        {/* <form action="/posts/1?_method=PATCH" method="post"> */}
        {/* ... */}
    </form>
);
```

If your form action supports multiple methods and would like to specify a method, you can invoke additional methods on the `form`:

```tsx
import { store, update } from "@/actions/App/Http/Controllers/PostController";

const Page = () => (
    <form {...update.form.put(1)}>
        {/* <form action="/posts/1?_method=PUT" method="post"> */}
        {/* ... */}
    </form>
);
```

## Query Parameters

All Wayfinder methods accept an optional, final `options` argument to which you may pass a `query` object. This object can be used to append query parameters onto the resulting URL:

```ts
import { show } from "@/actions/App/Http/Controllers/PostController";

const options = {
    query: {
        page: 1,
        sort_by: "name",
    },
};

show(1, options); // { url: "/posts/1?page=1&sort_by=name", method: "get" }
show.get(1, options); // { url: "/posts/1?page=1&sort_by=name", method: "get" }
show.url(1, options); // "/posts/1?page=1&sort_by=name"
show.form.head(1, options); // { action: "/posts/1?page=1&sort_by=name&_method=HEAD", method: "get" }
```

You can also merge with the URL's existing parameters by passing a `mergeQuery` object instead:

```ts
import { show } from "@/actions/App/Http/Controllers/PostController";

// window.location.search = "?page=1&sort_by=category&q=shirt"

const options = {
    mergeQuery: {
        page: 2,
        sort_by: "name",
    },
};

show.url(1, options); // "/posts/1?page=2&sort_by=name&q=shirt"
```

If you would like to remove a parameter from the resulting URL, define the value as `null` or `undefined`:

```ts
import { show } from "@/actions/App/Http/Controllers/PostController";

// window.location.search = "?page=1&sort_by=category&q=shirt"

const options = {
    mergeQuery: {
        page: 2,
        sort_by: null,
    },
};

show.url(1, options); // "/posts/1?page=2&q=shirt"
```

## Current Route Detection

Laravel Wayfinder provides a powerful `currentRoute` function that allows you to check if the current page matches a specific named route or retrieve the current URL.

### Basic Usage

The `currentRoute` function can be used in two ways:

```ts
import { currentRoute } from "@/wayfinder";

// Get current URL
const url = currentRoute(); // Returns: "https://example.com/posts/123"

// Check if current route matches
currentRoute("posts.index"); // Returns: true or false
currentRoute("posts.show"); // Returns: true or false
```

### Return Types

The function has different return types based on usage:

```ts
currentRoute(); // string - current URL
currentRoute("route.name"); // boolean - route match
currentRoute("route.name", params); // boolean - route match with params

// Both of these work the same way:
// Current URL: https://example.com/posts/
// Route URL:   https://example.com/posts
currentRoute("posts.index"); // Returns: true
```

### Route Parameters

You can check routes with parameters by passing them as the second argument:

```ts
// Single parameter (string or number)
currentRoute("posts.show", 123); // Matches /posts/123
currentRoute("posts.show", "my-slug"); // Matches /posts/my-slug

// Named parameters (object)
currentRoute("posts.show", { post: 123 });
currentRoute("export", { report: "sales", export: "pdf" });
```

### Query Parameters

Check routes with query parameters using an object:

```ts
// URL: https://example.com/posts?page=2&sort=name
currentRoute("posts.index", { page: 2, sort: "name" }); // true
currentRoute("posts.index", { page: 1 }); // false - doesn't match
currentRoute("posts.index", { page: 2 }); // true - partial match
```

### Mixed Parameters

Combine route parameters and query parameters in a single object:

```ts
// URL: https://example.com/posts/123?edit=true&tab=settings
currentRoute("posts.show", {
    post: 123, // Route parameter
    edit: true, // Query parameter
    tab: "settings", // Query parameter
}); // Returns: true
```

### Array Parameters

Arrays are always treated as query parameters, never as route parameters:

```ts
// URL: https://example.com/posts/123
currentRoute("posts.show", { post: ["12"] }); // false - array is query param, not route param

// URL: https://example.com/posts/123?tags[]=red&tags[]=blue
currentRoute("posts.show", {
    post: 123,
    tags: ["red", "blue"],
}); // true - matches query parameters
```

### Wildcard Matching

Use wildcards to match multiple routes:

```ts
currentRoute("posts.*"); // Matches posts.index, posts.show, posts.create, etc.
currentRoute("admin.*"); // Matches admin.dashboard, admin.users, etc.
currentRoute("api.v1.*"); // Matches api.v1.tasks, api.v1.users, etc.
```

### TypeScript Support

The `currentRoute` function is fully typed with your application's routes:

```ts
// TypeScript will autocomplete available route names
currentRoute("posts.show", 123); // ✅ Valid
currentRoute("invalid.route"); // ❌ TypeScript error
```

### Edge Cases & Special Handling

The function handles various edge cases and special scenarios:

```ts
// Trailing slash normalization
// Current URL: https://example.com/dashboard/
// Route URL:   https://example.com/dashboard
currentRoute("dashboard"); // Returns: true

// URL encoding/decoding
// Current URL: https://example.com/download/42/my%2Dfile(1).pdf
currentRoute("pdf.download", {
    pdfId: 42,
    fileName: "my-file(1).pdf",
}); // Returns: true

// Optional parameters
// Current URL: https://example.com/download/42
// Route: /download/{pdfId}/{fileName?}
currentRoute("pdf.download", { pdfId: 42 }); // Returns: true
currentRoute("pdf.download", { pdfId: 42, fileName: "optional.pdf" }); // Returns: false (not in URL)
```

### Complex Query Parameters

Handle nested and complex query parameter structures:

```ts
// Nested query parameters
// URL: https://example.com/posts?meta[author]=john&meta[status]=draft&settings[theme]=dark
currentRoute("posts.index", {
    meta: { author: "john", status: "draft" },
    settings: { theme: "dark" },
}); // Returns: true

// Array query parameters (both formats)
// URL: https://example.com/posts?tags[]=red&tags[]=blue&categories[0]=tech&categories[1]=news
currentRoute("posts.index", {
    tags: ["red", "blue"],
    categories: ["tech", "news"],
}); // Returns: true

// Mixed array formats
// URL: https://example.com/posts?nice[0]=a&nice[1]=12
currentRoute("posts.index", {
    nice: ["a", "12"],
}); // Returns: true
```

### Practical Examples

Common real-world usage patterns:

```ts
// Navigation highlighting
const isActive = currentRoute("posts.show", { post: postId });
// Use in React: className={isActive ? "active" : ""}

// Conditional rendering
if (currentRoute("admin.*")) {
    // Show admin navigation
}

// Form validation
if (currentRoute("posts.edit", { post: postId })) {
    // Enable edit mode
}

// Analytics tracking
if (currentRoute("checkout.*")) {
    // Track checkout funnel
}
```

### Advanced Features

The function handles complex scenarios including:

-   **URL encoding/decoding** for special characters in route parameters
-   **Optional parameters** in route definitions
-   **Nested query parameters** with proper validation
-   **Trailing slash normalization**
-   **Route parameter extraction** from current URL
-   **Array parameter handling** with both `param[]` and `param[0], param[1]` formats

```ts
// Handles encoded URLs
currentRoute("pdf.download", {
    pdfId: 42,
    fileName: "my-file(1).pdf",
}); // Works with encoded filenames

// Validates complex query structures
currentRoute("posts.index", {
    page: 2,
    search: "hello world",
    tags: ["a b", "c"],
    filters: { category: "tech", status: "active" },
}); // Matches nested query parameters
```

### Common Pitfalls & Best Practices

Avoid these common mistakes and follow best practices:

```ts
// ❌ Don't: Arrays as route parameters
currentRoute("posts.show", { post: ["12"] }); // false - arrays are query params only

// ✅ Do: Use arrays for query parameters
currentRoute("posts.show", {
    post: 123,
    tags: ["red", "blue"],
}); // true - post is route param, tags is query param

// ❌ Don't: Case-sensitive route names
currentRoute("Posts.Show"); // false - route names are case-sensitive

// ✅ Do: Use exact route names
currentRoute("posts.show"); // true

// ❌ Don't: Expect partial query parameter matches
currentRoute("posts.index", { page: 2, sort: "name" }); // false if URL only has page=2

// ✅ Do: Match all expected parameters
currentRoute("posts.index", { page: 2 }); // true - partial match is allowed
```

### Performance Tips

Optimize your usage for better performance:

```ts
// Cache route checks for repeated use
const isPostRoute = currentRoute("posts.*");
if (isPostRoute) {
    // Handle post routes
}

// Use wildcards for multiple route checks
const isAdminRoute = currentRoute("admin.*");
const isApiRoute = currentRoute("api.*");

// Avoid complex parameter objects in hot paths
// Instead of: currentRoute("posts.show", complexObject)
// Use: currentRoute("posts.show", { post: postId })
```

## Wayfinder and Inertia

When using [Inertia](https://inertiajs.com), you can pass the result of a Wayfinder method directly to the `submit` method of `useForm`, it will automatically resolve the correct URL and method:

[https://inertiajs.com/forms#wayfinder](https://inertiajs.com/forms#wayfinder)

```ts
import { useForm } from "@inertiajs/react";
import { store } from "@/actions/App/Http/Controllers/PostController";

const form = useForm({
    name: "My Big Post",
});

form.submit(store()); // Will POST to `/posts`...
```

You may also use Wayfinder in conjunction with Inertia's `Link` component:

[https://inertiajs.com/links#wayfinder](https://inertiajs.com/links#wayfinder)

```tsx
import { Link } from "@inertiajs/react";
import { show } from "@/actions/App/Http/Controllers/PostController";

const Nav = () => <Link href={show(1)}>Show me the first post</Link>;
```

## Contributing

Thank you for considering contributing to Wayfinder! You can read the contribution guide [here](.github/CONTRIBUTING.md).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

Please review [our security policy](https://github.com/laravel/wayfinder/security/policy) on how to report security vulnerabilities.

## License

Wayfinder is open-sourced software licensed under the [MIT license](LICENSE.md).
