import { execFileSync } from "node:child_process";
import { readFileSync, rmSync } from "node:fs";
import path from "node:path";
import { expect, test } from "vitest";

const testbench = path.join(__dirname, "../vendor/bin/testbench");

const generateWithAppUrl = (appUrl: string, outputPath: string) => {
    rmSync(outputPath, { recursive: true, force: true });

    execFileSync(
        testbench,
        ["wayfinder:generate", `--path=${outputPath}`, "--with-form"],
        {
            env: {
                ...process.env,
                APP_URL: appUrl,
            },
        },
    );
};

test("prefixes generated URLs with APP_URL path", () => {
    const outputPath = "/tmp/wayfinder-app-url-path";

    generateWithAppUrl("http://localhost:8081/v2", outputPath);

    const contents = readFileSync(
        path.join(outputPath, "actions/App/Http/Controllers/PostController.ts"),
        "utf8",
    );

    expect(contents).toContain("url: '/v2/posts'");
    expect(contents).toContain("url: '/v2/posts/{post}'");
});

test("appends APP_URL port to explicit domain routes without a port", () => {
    const outputPath = "/tmp/wayfinder-app-url-port";

    generateWithAppUrl("https://localhost:8001", outputPath);

    const contents = readFileSync(
        path.join(
            outputPath,
            "actions/App/Http/Controllers/DomainController.ts",
        ),
        "utf8",
    );

    expect(contents).toContain("url: '//example.test:8001/fixed-domain/{param}'");
    expect(contents).toContain(
        "url: '//{defaultDomain?}.au:8001/default-parameters-domain/{param}'",
    );
});
