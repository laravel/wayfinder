import { expect, it } from "vitest";
import InvokableController from "../workbench/resources/js/actions/App/Http/Controllers/InvokableController";

it("exports default for invokable controllers", () => {
    expect(InvokableController.url()).toBe("/invokable-controller");
    expect(InvokableController()).toEqual({
        url: "/invokable-controller",
        method: "get",
    });
});
