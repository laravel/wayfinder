import { expect, test } from "vitest";
import { show } from "../workbench/resources/js/actions/App/Http/Controllers/ModelBindingController";

test("will detect model binding", () => {
    expect(show.url(1)).toBe("/users/1");
    expect(show.url({ user: 1 })).toBe("/users/1");
    expect(show.url([1])).toBe("/users/1");
});
