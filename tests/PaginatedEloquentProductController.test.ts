import { describe, expectTypeOf, test } from "vitest";

import type { App, Inertia } from "../workbench/resources/js/wayfinder/types";

type NotAny<T> = 0 extends (1 & T) ? never : T;

describe("PaginatedEloquentProductController", () => {
    test("paginate narrows products.data to App.Models.Product[]", () => {
        expectTypeOf<NotAny<Inertia.Pages.PaginatedEloquentProducts.Paginate["products"]["data"]>>().toEqualTypeOf<
            App.Models.Product[]
        >();
    });

    test("simplePaginate narrows products.data to App.Models.Product[]", () => {
        expectTypeOf<NotAny<Inertia.Pages.PaginatedEloquentProducts.SimplePaginate["products"]["data"]>>().toEqualTypeOf<
            App.Models.Product[]
        >();
    });

    test("cursorPaginate narrows products.data to App.Models.Product[]", () => {
        expectTypeOf<NotAny<Inertia.Pages.PaginatedEloquentProducts.CursorPaginate["products"]["data"]>>().toEqualTypeOf<
            App.Models.Product[]
        >();
    });
});
