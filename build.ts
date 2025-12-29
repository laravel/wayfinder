import { execSync } from "node:child_process";
import path from "node:path";

const testbenchDir = path.join(__dirname, "vendor", "bin", "testbench");
const baseDir = path.join(__dirname, "workbench");
const appDir = path.join(baseDir, "app");

const artisan = (command: string, env: Record<string, string> = {}): void =>
    console.error(
        execSync(`${testbenchDir} ${command}`, {
            env: { ...process.env, ...env },
        }).toString("utf8"),
    );

export function setup(): void {
    try {
        process.env.WAYFINDER_CACHE_ROUTES
            ? artisan("route:cache")
            : artisan("route:clear");

        artisan(
            `wayfinder:generate --path=workbench/resources/js/wayfinder --app-path=${appDir} --base-path=${baseDir}`,
            { VITE_APP_NAME: "Workbench" },
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
