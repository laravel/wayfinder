import { execSync } from "node:child_process";
import { copyFileSync, rmSync } from "node:fs";
import path from "node:path";

const testbenchDir = path.join(__dirname, "vendor", "bin", "testbench");
const baseDir = path.join(__dirname, "workbench");
const appDir = path.join(baseDir, "app");
const jsDir = path.join(baseDir, "resources", "js");

const artisan = (command: string): void =>
    console.error(execSync(`${testbenchDir} ${command}`).toString("utf8"));

export function setup(): void {
    try {
        copyFileSync(
            path.join(baseDir, ".env.example"),
            path.join(baseDir, ".env"),
        );

        // The output directory is gitignored, so a checkout carries no
        // guarantee about its contents: wipe it so the tests and the type
        // check only ever see what this run generated.
        rmSync(jsDir, { recursive: true, force: true });

        if (process.env.WAYFINDER_CACHE_ROUTES) {
            artisan("route:cache");
        } else {
            artisan("route:clear");
        }

        artisan(
            `wayfinder:generate --path=workbench/resources/js/wayfinder --app-path=${appDir} --base-path=${baseDir}`,
        );
    } catch (error) {
        console.error(
            `Wayfinder build error\n----------${error.output}\n----------`,
        );

        process.exit(1);
    }
}

export function teardown(): void {
    artisan("route:clear");
}
