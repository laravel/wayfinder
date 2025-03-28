import { expect, it } from "vitest";
import { same } from "../workbench/resources/js/actions/App/Http/Controllers/TwoRoutesSameActionController";

it("creates a keyed dictionary of routes for multiple routes pointing to the same action", () => {
    expect(same["/two-routes-one-action-1"].url()).toBe(
        "/two-routes-one-action-1",
    );
    expect(same["/two-routes-one-action-1"]()).toEqual({
        url: "/two-routes-one-action-1",
        method: "get",
    });

    expect(same["/two-routes-one-action-2"].url()).toBe(
        "/two-routes-one-action-2",
    );
    expect(same["/two-routes-one-action-2"]()).toEqual({
        url: "/two-routes-one-action-2",
        method: "get",
    });
});
