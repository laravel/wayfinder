import { execSync } from "node:child_process";
import path from "node:path";

export default function setup() {
    try {
        const testbenchDir = path.join(__dirname, "vendor", "bin", "testbench");

        execSync(
            `${testbenchDir} wayfinder:generate --path=workbench/resources/js --with-form`,
        );
    } catch (error) {
        console.error(
            `Wayfinder build error.\n----------${error.output}\n----------`,
        );
        process.exit(1);
    }
}
