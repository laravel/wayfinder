# Wayfinder for Laravel

Bring your back end to your front end.

Wayfinder generates TypeScript files for all of your routes and actions, allowing you to access them directly in your client side code. You can import controller methods as you've defined them in PHP and use them as functions.

## Installation

```
composer require laravel/wayfinder
```

If you'd like to watch your files for changes, you can use `vite-plugin-run`:

```
npm i -D vite-plugin-run
```

Then in your `vite.config.js`:

```ts
import { run } from 'vite-plugin-run'

export default defineConfig({
    plugins: [
        // ...
        run([
            {
                name: "wayfinder",
                run: ["php", "artisan", "wayfinder:generate"],
                pattern: ["routes/*.php", "app/**/Http/**/*.php"],
            },
        ]),
    ],
});
```

It may also be useful to register aliases for importing the generated files into your app:

```ts
export default defineConfig({
    // ...
    resolve: {
        alias: {
            '@actions/': './resources/js/actions',
            '@routes/': './resources/js/routes',
        }
    }
});
```

## Generate Command

To generate TypeScript:

```
php artisan wayfinder:generate
```

By default, Wayfinder generates files in two directories (`actions` and `routes`) within `resources/js`, but you can configure the base path:

```
php artisan wayfinder:generate --base=resources/js/wayfinder
```

If you want to skip `actions` generation:

```
php artisan wayfinder:generate --skip-actions
```

Skip `routes` generation:

```
php artisan wayfinder:generate --skip-routes
```

## Usage

Wayfinder functions returns an object that contains the resolved url and default method:

```ts
import { show } from '@actions/App/Http/Controllers/PostController';

show(1); // { uri: "/posts/1", method: "get" }
```

If you just need the URL, or would like to choose a method from the verbs defined on the server, you can chain off of the import:

```ts
import { show } from '@actions/App/Http/Controllers/PostController';

show.url(1); // "/posts/1"
show.head(1); // { uri: "/posts/1", method: "head" }
```

Wayfinder accepts a variety of shapes for the argument:

```ts
import { show, update } from '@actions/App/Http/Controllers/PostController';

// Single param action
show(1);
show({ id: 1 });

// Multiple param action
update([1, 2]);
update({ post: 1, author: 2 })
update({ post: { id: 1 }, author: { id: 2 } })
```

If you've specified a key for the param binding, Wayfinder will detect that and you can pass that in as an object:

```ts
import { show } from '@actions/App/Http/Controllers/PostController';

// Route is /posts/{post:slug}
show('my-new-post');
show({ slug: 'my-new-post' });
```

Invokable controller? No problem, just call it directly:

```ts
import StorePostController from '@actions/App/Http/Controllers/StorePostController';

StorePostController();
```

You can import any part of the namespace as well:

```ts
import PostController from '@actions/App/Http/Controllers/PostController';

PostController.show(1);
```

Note: importing this way prevents the `PostController` from being tree-shaken, so all `PostController` actions will end up in your final bundle.

Wayfinder can generate methods for your named routes as well:

```ts
import { show } from '@routes/post';

// Named route is `post.show`
show(1); // { uri: "/posts/1", method: "get" }
```

If you're using a conventional form submission, Wayfinder can help you out there as well:

```tsx
import { store, update } from '@actions/App/Http/Controllers/PostController';

const Page = () => (
    <form {...store.form(1)}> {/* { action: "/posts", method: "post" } */}
        {/* ... */}
    </form>
)

const Page = () => (
    <form {...update.form.patch(1)}> {/* { action: "/posts/1?_method=PATCH", method: "post" } */}
        {/* ... */}
    </form>
)
```

## Wayfinder + Inertia

You can pass the result of a Wayfinder method directly to the `submit` method of `useForm`, it will automatically resolve the correct URI and method:

```ts
import { useForm } from '@inertiajs/react'
import { store } from '@actions/App/Http/Controllers/PostController';

const form = useForm({
    name: 'My Big Post',
});

form.submit(store()) // Will POST to `/posts`
```

Wayfinder also pairs well with the `Link` component:

```tsx
import { Link } from '@inertiajs/react'
import { show } from '@actions/App/Http/Controllers/PostController';

const Nav = () => (
    <Link href={show(1)}>Show me the first post</Link>
)
```
