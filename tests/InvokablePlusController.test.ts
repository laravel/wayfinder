import { expect, it } from "vitest";
import InvokablePlusController from "../workbench/resources/js/actions/App/Http/Controllers/InvokablePlusController";

it("exports default and methods for invokable controllers", () => {
    expect(InvokablePlusController.url()).toBe("/invokable-plus-controller");
    expect(InvokablePlusController()).toEqual({
        url: "/invokable-plus-controller",
        method: "get",
    });

    expect(InvokablePlusController.store.url()).toBe(
        "/invokable-plus-controller",
    );
    expect(InvokablePlusController.store()).toEqual({
        url: "/invokable-plus-controller",
        method: "post",
    });
});
