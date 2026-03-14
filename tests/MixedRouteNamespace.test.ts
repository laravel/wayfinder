import { expect, it } from "vitest";
import { items } from "../workbench/resources/js/wayfinder/routes/mixed";
import { edit, update } from "../workbench/resources/js/wayfinder/routes/mixed/items";

/**
 * This test verifies that Wayfinder correctly handles route namespaces where
 * a parent path has both a direct route AND child routes with parameters.
 *
 * For example:
 *   - mixed.items (index route at /mixed/items)
 *   - mixed.items.edit (edit route at /mixed/items/{item})
 *
 * When Laravel's undot() processes these route names, it creates a mixed array
 * where "items" contains both:
 *   - A numeric key (0) with the index route's VariableBuilder
 *   - A string key ("edit") with the edit route's data
 *
 * This caused a TypeError in formatNamespaced() because array_is_list() was
 * called on a VariableBuilder object instead of an array.
 */
it("handles mixed route namespaces with index and parameterized children", () => {
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
