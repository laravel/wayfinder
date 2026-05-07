import { expect, it } from "vitest";
import dashedParent from "../workbench/resources/js/wayfinder/routes/dashed-parent";

it("camel-cases hyphenated directory names in barrel files", () => {
    expect(dashedParent.admin.items.url()).toBe("/dashed-parent/admin/items");
    expect(dashedParent.admin.items()).toEqual({
        url: "/dashed-parent/admin/items",
        method: "get",
    });
});
