import { expect, it } from "vitest";
import { dashboard, invokable } from "../workbench/resources/js/routes";
import { edit } from "../workbench/resources/js/routes/posts";

it("exports named routes", () => {
    expect(edit.url(1)).toBe("/posts/1/edit");
    expect(edit(1)).toEqual({
        url: "/posts/1/edit",
        method: "get",
    });

    expect(dashboard.url()).toBe("/dashboard");
    expect(dashboard()).toEqual({
        url: "/dashboard",
        method: "get",
    });

    expect(invokable.url()).toBe("/named-invokable-controller");
    expect(invokable()).toEqual({
        url: "/named-invokable-controller",
        method: "get",
    });
});
