import { expect, it } from "vitest";
import { myDashedRoute } from "../workbench/resources/js/routes";

it("handles dashed route names", () => {
    expect(myDashedRoute.url()).toBe("/dashed-route");
    expect(myDashedRoute()).toEqual({
        url: "/dashed-route",
        method: "get",
    });
});
