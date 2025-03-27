import { expect, it } from "vitest";
import { edit } from "../workbench/resources/js/routes/posts";

it("exports default and methods for invokable controllers", () => {
    expect(edit.url(1)).toBe("/posts/1/edit");
    expect(edit(1)).toEqual({
        uri: "/posts/1/edit",
        method: "get",
    });
});
