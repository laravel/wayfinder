import { execSync } from "node:child_process";

export default function setup() {
    try {
        execSync(
            "vendor/bin/testbench wayfinder:generate --base=workbench/resources/js --with-form",
        );
    } catch (error) {
        console.error(
            `Wayfinder build error.\n----------${error.output}\n----------`,
        );
        process.exit(1);
    }
}
