import { expect, it } from "vitest";
import storage from "../workbench/resources/js/wayfinder/routes/storage";

it("can import storage routes", () => {
    expect(storage.export("file-name")).toEqual({
        url: "/storage/file-name",
        method: "get",
    });
});
