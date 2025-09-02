import { expect, test } from "vitest";
import { exportMethod } from "../workbench/resources/js/routes/index";

test("export param", () => {
    expect(exportMethod.url({ report: "a", export: "b" })).toBe("/export/a/b");
    expect(exportMethod.url(["a", "b"])).toBe("/export/a/b");
});
