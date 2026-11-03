import { expect, it } from "vitest";
import Controllers from "../workbench/resources/js/wayfinder/App/Http/Controllers";

it("lists barrel entries in a stable, sorted order", () => {
    const files = Object.keys(Controllers).filter((key) => key.endsWith("Controller"));

    expect(files.length).toBeGreaterThan(1);
    expect(files).toEqual([...files].sort());
});
