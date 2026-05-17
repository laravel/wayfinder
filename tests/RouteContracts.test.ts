import { expectTypeOf, test } from "vitest";
import { store } from "../workbench/resources/js/wayfinder/App/Http/Controllers/PostController";
import { show } from "../workbench/resources/js/wayfinder/App/Http/Controllers/ResourceTestController";
import type { RequestOf, ResponseOf } from "../workbench/resources/js/wayfinder";
import type { App } from "../workbench/resources/js/wayfinder/types";

test("infers request types from route functions", () => {
    expectTypeOf<RequestOf<typeof store>>().toEqualTypeOf<
        App.Http.Controllers.PostController.Store.Request
    >();

    expectTypeOf<RequestOf<typeof store.post>>().toEqualTypeOf<
        App.Http.Controllers.PostController.Store.Request
    >();

    expectTypeOf<RequestOf<typeof store.form>>().toEqualTypeOf<
        App.Http.Controllers.PostController.Store.Request
    >();
});

test("infers response types from route functions", () => {
    expectTypeOf<ResponseOf<typeof show>>().toEqualTypeOf<
        App.Http.Controllers.ResourceTestController.Show.Response
    >();

    expectTypeOf<ResponseOf<ReturnType<typeof show>>>().toEqualTypeOf<
        App.Http.Controllers.ResourceTestController.Show.Response
    >();

    expectTypeOf<ResponseOf<typeof show.get>>().toEqualTypeOf<
        App.Http.Controllers.ResourceTestController.Show.Response
    >();

    expectTypeOf<RequestOf<typeof show>>().toEqualTypeOf<
        App.Http.Controllers.ResourceTestController.Show.Request
    >();
});

test("does not infer contracts from plain route-like objects", () => {
    expectTypeOf<ResponseOf<{ url: string }>>().toEqualTypeOf<never>();
});
