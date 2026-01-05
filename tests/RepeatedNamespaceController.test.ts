import { expect, it } from "vitest";
import { readFileSync } from "node:fs";
import path from "node:path";

it("avoids conflicting barrel identifiers when namespace segment repeats", () => {
    const indexPath = path.join(
        __dirname,
        "../workbench/resources/js/actions/App/Prism/index.ts",
    );

    const contents = readFileSync(indexPath, "utf8");

    expect(contents).toContain("import Prism from './Prism'");
    expect(contents).toContain("const PrismNamespace = {");
    expect(contents).toContain("export default PrismNamespace");
});
