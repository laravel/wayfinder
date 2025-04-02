import { expect, test } from "vitest";
import { show } from "../workbench/resources/js/actions/App/Http/Controllers/AnonymousMiddlewareController";

test("will allow for closure middleware", () => {
    expect(show.url()).toBe("/anonymous-middleware");
});
