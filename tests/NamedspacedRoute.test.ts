import { expect, it } from "vitest";
import { store } from "../workbench/resources/js/routes/namespaced/my-package";

it("can access a namespaced route", () => {
    expect(store.url()).toBe("/package-route");
    expect(store()).toEqual({
        url: "/package-route",
        method: "get",
    });
});
