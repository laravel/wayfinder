import { expect, it } from "vitest";
import {
    dashboard,
    invalid_js_name,
    invokable,
} from "../workbench/resources/js/wayfinder/routes";
import { edit } from "../workbench/resources/js/wayfinder/routes/posts";

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

    expect(invalid_js_name.url()).toBe("/invalid-js-name");
    expect(invalid_js_name()).toEqual({
        url: "/invalid-js-name",
        method: "get",
    });
});
