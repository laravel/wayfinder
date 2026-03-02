import { spawnSync } from "node:child_process";
import { existsSync, rmSync } from "node:fs";
import path from "node:path";
import { expect, test } from "vitest";

const testbench = path.join(__dirname, "../vendor/bin/testbench");

const stripAnsi = (value: string) =>
    value.replaceAll(/\u001b\[[0-9;]*m/g, "");

const runGenerate = (args: string[]) => {
    const result = spawnSync(testbench, ["wayfinder:generate", ...args], {
        encoding: "utf8",
    });

    if (result.status !== 0) {
        throw new Error(
            `wayfinder:generate failed\nstdout:\n${result.stdout}\nstderr:\n${result.stderr}`,
        );
    }

    return stripAnsi(`${result.stdout}\n${result.stderr}`);
};

test("supports --only route filters", () => {
    const outputPath = "/tmp/wayfinder-only-post-routes";

    rmSync(outputPath, { recursive: true, force: true });

    const output = runGenerate([
        `--path=${outputPath}`,
        "--only=name:posts.*",
        "--with-form",
    ]);

    expect(
        existsSync(
            path.join(
                outputPath,
                "actions/App/Http/Controllers/PostController.ts",
            ),
        ),
    ).toBe(true);
    expect(
        existsSync(
            path.join(
                outputPath,
                "actions/App/Http/Controllers/DomainController.ts",
            ),
        ),
    ).toBe(false);

    expect(existsSync(path.join(outputPath, "routes/posts/index.ts"))).toBe(
        true,
    );
    expect(existsSync(path.join(outputPath, "routes/dashboard/index.ts"))).toBe(
        false,
    );

    expect(output).toContain("[Wayfinder] Filter report");
    expect(output).toMatch(/Routes selected: \d+/);
    expect(output).toContain("Skipped by --only:");
});

test("supports --except controller filters", () => {
    const outputPath = "/tmp/wayfinder-except-post-controller";

    rmSync(outputPath, { recursive: true, force: true });

    runGenerate([
        `--path=${outputPath}`,
        "--except=controller:App\\Http\\Controllers\\PostController",
        "--with-form",
    ]);

    expect(
        existsSync(
            path.join(
                outputPath,
                "actions/App/Http/Controllers/PostController.ts",
            ),
        ),
    ).toBe(false);
    expect(
        existsSync(
            path.join(
                outputPath,
                "actions/App/Http/Controllers/DomainController.ts",
            ),
        ),
    ).toBe(true);
});

test("prints skipped route examples with --report", () => {
    const outputPath = "/tmp/wayfinder-filter-report";

    rmSync(outputPath, { recursive: true, force: true });

    const output = runGenerate([
        `--path=${outputPath}`,
        "--only=name:posts.*",
        "--report",
        "--with-form",
    ]);

    expect(output).toContain("Skipped route examples:");
    expect(output).toContain("reason: only");
});
