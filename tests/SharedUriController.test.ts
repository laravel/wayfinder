import { expect, it } from "vitest";
import SharedUriController from "../workbench/resources/js/actions/App/Http/Controllers/SharedUriController";

it("keys routes sharing a URI by verb", () => {
    expect(SharedUriController["get /shared-uri/{name}"].url("test")).toBe(
        "/shared-uri/test",
    );
    expect(SharedUriController["get /shared-uri/{name}"]("test")).toEqual({
        url: "/shared-uri/test",
        method: "get",
    });

    expect(SharedUriController["post /shared-uri/{name}"].url("test")).toBe(
        "/shared-uri/test",
    );
    expect(SharedUriController["post /shared-uri/{name}"]("test")).toEqual({
        url: "/shared-uri/test",
        method: "post",
    });

    expect(SharedUriController["put|patch /shared-uri/{name}"].url("test")).toBe(
        "/shared-uri/test",
    );
    expect(SharedUriController["put|patch /shared-uri/{name}"]("test")).toEqual({
        url: "/shared-uri/test",
        method: "put",
    });
});
