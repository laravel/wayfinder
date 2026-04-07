import { expect, test } from "vitest";
import { options } from "../workbench/resources/js/wayfinder/App/Http/Controllers/NavigationItemController";

test("does not shadow a generated method named options", () => {
    expect(options({ item: 1 })).toEqual({
        url: "/navigation-items/1/options",
        method: "get",
    });

    expect(
        options.url(
            { item: 1 },
            {
                query: {
                    page: 2,
                },
            },
        ),
    ).toBe("/navigation-items/1/options?page=2");
});
