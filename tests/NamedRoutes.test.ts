import { expect, it } from "vitest";
import routes, {
    dashboard,
    invalid_js_name,
    invokable,
    myDashedRoute,
} from "../workbench/resources/js/wayfinder/routes";
import projects from "../workbench/resources/js/wayfinder/routes/projects";
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

it("quotes barrel keys that are not valid JS identifiers", () => {
    expect(myDashedRoute.url()).toBe("/dashed-route");
    expect(routes["my-dashed-route"].url()).toBe("/dashed-route");
    expect(routes["invalid#js@name"].url()).toBe("/invalid-js-name");

    expect(projects.application["customer-sector"].url()).toBe(
        "/projects/application/customer-sector",
    );
});
