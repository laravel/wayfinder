import { readFileSync } from "fs";
import { join } from "path";
import { describe, expect, it } from "vitest";
import storage from "../workbench/resources/js/wayfinder/routes/storage";

it("can import storage routes", () => {
    expect(storage.export("file-name")).toEqual({
        url: "/storage/file-name",
        method: "get",
    });
});

describe("index file uses safe names for JS reserved words", () => {
    const indexFile = readFileSync(
        join(__dirname, "../workbench/resources/js/wayfinder/routes/storage/index.ts"),
        "utf-8",
    );

    it("uses safe name in Object.assign for reserved word sub-namespaces", () => {
        // "export" is a JS reserved word and can't be used as a bare identifier.
        // The index file should never use bare "export" in Object.assign.
        // On Laravel 12.53+ (storage.export.upload route exists), it should use "exportMethod".
        expect(indexFile).not.toMatch(/Object\.assign\(export\b[^M]/);

        if (indexFile.includes("Object.assign")) {
            expect(indexFile).toMatch(/Object\.assign\(exportMethod,/);
        }
    });
});
