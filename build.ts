import { execSync } from "node:child_process";
import path from "node:path";

const testbenchDir = path.join(__dirname, "vendor", "bin", "testbench");

const artisan = (command: string): void => console.error(execSync(`${testbenchDir} ${command}`).toString('utf8'))

export function setup(): void {
    try {
        process.env.WAYFINDER_CACHE_ROUTES
            ? artisan('route:cache')
            : artisan('route:clear')

        artisan('wayfinder:generate --path=workbench/resources/js --with-form')
    } catch (error) {
        console.error(`Wayfinder build error\n----------${error.output}\n----------`);

        process.exit(1);
    }
}

export function teardown(): void {
    artisan('route:clear')
}
