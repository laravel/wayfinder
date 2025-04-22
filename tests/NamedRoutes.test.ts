import { expect, it } from "vitest";
import { edit } from "../workbench/resources/js/routes/posts";

it("exports named routes", () => {
    expect(edit.url(1)).toBe("/posts/1/edit");
    expect(edit(1)).toEqual({
        url: "/posts/1/edit",
        method: "get",
    });
});
