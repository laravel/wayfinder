import { expect, it } from "vitest";
import { items } from "../workbench/resources/js/wayfinder/routes/mixed";
import { edit, update } from "../workbench/resources/js/wayfinder/routes/mixed/items";

it("handles routes where a parent has both a direct route and child routes", () => {
    expect(items.url()).toBe("/mixed/items");
    expect(items()).toEqual({
        url: "/mixed/items",
        method: "get",
    });

    expect(edit.url(1)).toBe("/mixed/items/1");
    expect(edit(1)).toEqual({
        url: "/mixed/items/1",
        method: "get",
    });

    expect(update.url(1)).toBe("/mixed/items/1");
    expect(update(1)).toEqual({
        url: "/mixed/items/1",
        method: "patch",
    });
});
