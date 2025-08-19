import js from "@eslint/js";
import tseslint from "typescript-eslint";

export default tseslint.config(
    js.configs.recommended,
    ...tseslint.configs.recommended,
    {
        files: ["workbench/resources/js/**/*.{ts,tsx}"],
        rules: {
            "no-duplicate-imports": "error",
        },
    },
);
