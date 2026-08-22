import { existsSync, readFileSync } from "fs";
import { join } from "path";
import { describe, expect, test } from "vitest";

describe("Ignore", () => {
    const wayfinderPath = join(__dirname, "../workbench/resources/js/wayfinder");
    const types = () =>
        readFileSync(join(wayfinderPath, "types.d.ts"), "utf-8");

    test("nothing is generated for a marked controller", () => {
        expect(types()).not.toContain("IgnoredController");
        expect(
            existsSync(
                join(wayfinderPath, "App/Http/Controllers/IgnoredController.ts"),
            ),
        ).toBe(false);
    });

    test("a marked method is dropped and its siblings are kept", () => {
        const controller = readFileSync(
            join(wayfinderPath, "App/Http/Controllers/SecretsController.ts"),
            "utf-8",
        );

        expect(controller).toContain("export const index");
        expect(controller).not.toContain("reveal");
        expect(types()).not.toContain("Inertia.Pages.Reveal");
    });

    test("a marked page prop is dropped from the page type", () => {
        const secrets = types()
            .split("export type Secrets")[1]
            ?.split("export type")[0];

        expect(secrets).toBeDefined();
        expect(secrets).toContain("name: string");
        expect(secrets).toContain("email: string");
        expect(secrets).not.toContain("socialSecurityNumber");
    });

    test("a marked prop nested in a page prop is dropped", () => {
        const secrets = types()
            .split("export type Secrets")[1]
            ?.split("export type")[0];

        expect(secrets).toContain("label: string");
        expect(secrets).not.toContain("routingNumber");
    });

    test("a marked toArray leaves the action without a response shape", () => {
        const controller =
            types().split("export namespace SecretsController")[1] ?? "";
        const resource = controller
            .split("export namespace Resource {")[1]
            ?.split("export namespace")[0];

        expect(resource).toBeDefined();
        expect(resource).toContain("export type Request");
        expect(resource).not.toContain("export type Response");
        expect(types()).not.toContain("socialSecurityNumber");
    });

    test("nothing is generated for a marked model", () => {
        expect(types()).not.toContain("AuditLog");
    });

    test("a marked relation is dropped from the model type", () => {
        const user = types()
            .split("export type User")[1]
            ?.split("export type")[0];

        expect(user).toBeDefined();
        expect(user).not.toContain("auditEntries");
    });

    test("a case is dropped when its unless condition fails", () => {
        const sourceProvider = readFileSync(
            join(wayfinderPath, "App/Enums/SourceProvider.ts"),
            "utf-8",
        );

        expect(sourceProvider).toContain('export const Github = "github"');
        expect(sourceProvider).toContain("{ Github, Gitlab }");
        expect(sourceProvider).not.toContain("GitFake");
        expect(types()).toContain(
            'export type SourceProvider = "github" | "gitlab"',
        );
    });

    test("a case is dropped when its when condition passes", () => {
        const sourceProvider = readFileSync(
            join(wayfinderPath, "App/Enums/SourceProvider.ts"),
            "utf-8",
        );

        expect(sourceProvider).not.toContain("GitRetired");
        expect(types()).not.toContain("gitretired");
    });

    test("routes for marked actions are not registered", () => {
        const routeFiles = readFileSync(
            join(wayfinderPath, "routes/index.ts"),
            "utf-8",
        );

        expect(routeFiles).not.toContain("ignored");
    });
});
