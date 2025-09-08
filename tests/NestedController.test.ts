import { expect, it } from "vitest";
import nested from "../workbench/resources/js/routes/nested";

it("can handle conflicting nested route names", () => {
    expect(nested.child().url).toBe("/nested/controller/child");
    expect(nested.child.grandchild().url).toBe(
        "/nested/controller/child/grandchild",
    );
});
