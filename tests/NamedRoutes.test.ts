import { expect, it } from "vitest";
import { edit } from "../workbench/resources/js/routes/posts";
import { invokable, dashboard } from "../workbench/resources/js/routes";

it("exports named routes", () => {
    expect(edit.url(1)).toBe("/posts/1/edit");
    expect(edit(1)).toEqual({
        url: "/posts/1/edit",
        method: "get",
    });

    expect(dashboard.url()).toBe("/dashboard")
    expect(dashboard()).toEqual({
        url: "/dashboard",
        method: "get",
    });

    expect(invokable.url()).toBe("/invokable-controller")
    expect(invokable()).toEqual({
        url: "/invokable-controller",
        method: "get",
    });
});
