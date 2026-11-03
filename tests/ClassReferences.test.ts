import { describe, expectTypeOf, test } from "vitest";
import type { App, Inertia } from "../workbench/resources/js/wayfinder/types";

describe("class references", () => {
    type Props = Inertia.Pages.ClassReference.Show;

    test("a class without a generated type becomes unknown", () => {
        expectTypeOf<Props["price"]>().toBeUnknown();
    });

    test("Foo::class is a string, not an instance", () => {
        expectTypeOf<Props["ownerClass"]>().toEqualTypeOf<string>();
    });

    test("chained Inertia prop helpers keep the value they send", () => {
        expectTypeOf<Props["session"]>().toEqualTypeOf<{ id: number; name: string }>();
        expectTypeOf<Props["feed"]>().toEqualTypeOf<{ cursor: string } | undefined>();
    });

    test("a model keeps its generated type", () => {
        expectTypeOf<Props["owner"]>().toEqualTypeOf<App.Models.User>();
    });
});
