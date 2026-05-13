import { describe, expectTypeOf, test } from "vitest";

import type { App, Inertia } from "../workbench/resources/js/wayfinder/types";

type NotAny<T> = 0 extends (1 & T) ? never : T;

describe("PaginatedEloquentProductController", () => {
    test("Product::paginate()) emits expected types", () => {
        type Subject = Inertia.Pages.PaginatedEloquentProducts.Paginate["products"];

        expectTypeOf<NotAny<Subject["data"]>>().toEqualTypeOf< App.Models.Product[] >();

        // TODO: add generic return type to laravel/framework LengthAwarePaginator's linkCollection() docblock and this passes
        // expectTypeOf<NotAny<Subject["links"]>>().toEqualTypeOf<
        //     { url: string | null; label: string; active: boolean; page?: number | null }[]
        // >();

        expectTypeOf<NotAny<Subject["path"]>>().toEqualTypeOf<string | null>();
        expectTypeOf<NotAny<Subject["from"]>>().toEqualTypeOf<number | null>();
        expectTypeOf<NotAny<Subject["to"]>>().toEqualTypeOf<number | null>();
        expectTypeOf<NotAny<Subject["total"]>>().toEqualTypeOf<number>();
        expectTypeOf<NotAny<Subject["current_page"]>>().toEqualTypeOf<number>();
        expectTypeOf<NotAny<Subject["last_page"]>>().toEqualTypeOf<number>();
        expectTypeOf<NotAny<Subject["first_page_url"]>>().toEqualTypeOf<string>();
        expectTypeOf<NotAny<Subject["prev_page_url"]>>().toEqualTypeOf<string | null>();
        expectTypeOf<NotAny<Subject["next_page_url"]>>().toEqualTypeOf<string | null>();
        expectTypeOf<NotAny<Subject["last_page_url"]>>().toEqualTypeOf<string>();
        expectTypeOf<NotAny<Subject["total"]>>().toEqualTypeOf<number>();
        expectTypeOf<NotAny<Subject["per_page"]>>().toEqualTypeOf<number>();
    });

    test("simplePaginate narrows products.data to App.Models.Product[]", () => {
        type Subject = Inertia.Pages.PaginatedEloquentProducts.SimplePaginate["products"];

        expectTypeOf<NotAny<Subject["data"]>>().toEqualTypeOf<App.Models.Product[]>();
    });

    test("cursorPaginate narrows products.data to App.Models.Product[]", () => {
        type Subject = Inertia.Pages.PaginatedEloquentProducts.CursorPaginate["products"];

        expectTypeOf<NotAny<Subject["data"]>>().toEqualTypeOf<App.Models.Product[]>();
    });
});
