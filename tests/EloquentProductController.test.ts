import { describe, expectTypeOf, test } from "vitest";

import type { App, Inertia } from "../workbench/resources/js/wayfinder/types";

type NotAny<T> = 0 extends (1 & T) ? never : T;

describe("EloquentProductController", () => {
    test("Index narrows products to App.Models.Product[]", () => {
        expectTypeOf<NotAny<Inertia.Pages.EloquentProducts.Index["products"]>>().toEqualTypeOf<
            App.Models.Product[]
        >();
    });

    test("Show narrows product to App.Models.Product", () => {
        expectTypeOf<NotAny<Inertia.Pages.EloquentProducts.Show["product"]>>().toEqualTypeOf<
            App.Models.Product
        >();
    });
});
