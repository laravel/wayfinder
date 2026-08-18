import { execFileSync } from "node:child_process";
import { readFileSync, rmSync } from "node:fs";
import path from "node:path";
import { expect, test } from "vitest";
import { manyOptional } from "../workbench/resources/js-forced-root/actions/App/Http/Controllers/OptionalController";
import { index } from "../workbench/resources/js-forced-root/actions/App/Http/Controllers/PostController";
import { setUrlDefaults } from "../workbench/resources/js-forced-root/wayfinder";

const testbench = path.join(__dirname, "../vendor/bin/testbench");

const generateWithForcedRoot = (forcedRoot: string, outputPath: string) => {
    rmSync(outputPath, { recursive: true, force: true });

    execFileSync(
        testbench,
        ["wayfinder:generate", `--path=${outputPath}`, "--with-form"],
        {
            env: {
                ...process.env,
                WAYFINDER_FORCE_ROOT_URL: forcedRoot,
            },
        },
    );
};

test("does not freeze a forceRootUrl-derived domain into the generated URL", () => {
    const outputPath = "/tmp/wayfinder-force-root-origin";

    generateWithForcedRoot("https://tenant-a.example.test:8443", outputPath);

    const contents = readFileSync(
        path.join(outputPath, "actions/App/Http/Controllers/PostController.ts"),
        "utf8",
    );

    expect(contents).toContain("url: '{wayfinderOrigin?}/posts'");
    expect(contents).not.toContain(
        "url: 'https://tenant-a.example.test:8443/posts'",
    );
});

test("still bakes an explicit Route::domain() host literally even when forceRootUrl is active", () => {
    const outputPath = "/tmp/wayfinder-force-root-origin-explicit-domain";

    generateWithForcedRoot("https://tenant-a.example.test:8443", outputPath);

    const contents = readFileSync(
        path.join(
            outputPath,
            "actions/App/Http/Controllers/DomainController.ts",
        ),
        "utf8",
    );

    // The explicit domain host ("example.test") is untouched by forceRootUrl.
    // (Scheme forcing is a separate, pre-existing behavior this fix doesn't touch.)
    expect(contents).toContain("/fixed-domain/{param}'");
    expect(contents).not.toContain("wayfinderOrigin");
    expect(contents).toMatch(/url: '[a-z]+:\/\/example\.test\/fixed-domain/);
});

test("falls back to the generate-time origin when no runtime override is supplied", () => {
    expect(index.url()).toBe("https://tenant-a.example.test:8443/posts");
});

test("can be overridden at runtime via setUrlDefaults, like any other url default", () => {
    setUrlDefaults({ wayfinderOrigin: "https://tenant-b.example.test" });

    expect(index.url()).toBe("https://tenant-b.example.test/posts");

    setUrlDefaults({});
});

test("does not throw when only the origin is overridden and a route's own optional path parameters are left unset", () => {
    expect(() =>
        manyOptional.url({ wayfinderOrigin: "https://tenant-b.example.test" }),
    ).not.toThrow();

    expect(
        manyOptional.url({ wayfinderOrigin: "https://tenant-b.example.test" }),
    ).toBe("https://tenant-b.example.test/many-optional");
});

test("does not throw when the origin is overridden alongside a route's own leading optional path parameter", () => {
    expect(
        manyOptional.url({
            one: "1",
            wayfinderOrigin: "https://tenant-b.example.test",
        }),
    ).toBe("https://tenant-b.example.test/many-optional/1");
});

test("still throws when a route's own optional path parameter is skipped over, origin aside", () => {
    expect(() => manyOptional.url({ two: "2" })).toThrow();
});
