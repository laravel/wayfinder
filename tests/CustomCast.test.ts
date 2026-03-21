import { readFileSync } from "fs";
import { join } from "path";
import { describe, expect, test } from "vitest";

describe("Custom Cast Types", () => {
    const typesPath = join(
        __dirname,
        "../workbench/resources/js/wayfinder/types.d.ts",
    );
    const content = readFileSync(typesPath, "utf-8");

    describe("#[WayfinderType] on cast class", () => {
        test("property using SettingsCast produces exact type from attribute", () => {
            // 'options' is cast with SettingsCast which has #[WayfinderType('{ theme: "dark" | "light", notification_enabled: boolean }')]
            expect(content).toMatch(
                /export type User = \{.*?options: \{ theme: "dark" \| "light", notification_enabled: boolean \}/,
            );
        });

        test("property using custom cast does NOT produce unknown", () => {
            // Without our attribute, it would be 'unknown'
            expect(content).not.toMatch(
                /export type User = \{.*?options: unknown/,
            );
        });
    });

    describe("#[WayfinderPropertyType] on model class", () => {
        test("property with model-level override produces specific object type", () => {
            // 'meta' uses Laravel's native AsCollection cast, which is generic.
            // But it is overridden at the model level to be strictly typed.
            expect(content).toMatch(
                /export type User = \{.*?meta: \{ bio: string, timezone: string, social_links: Record<string, string> \}/,
            );
        });

        test("model-level override wins over generic framework types like unknown[]", () => {
            // Without the override, AsCollection would just generate an unknown array type.
            expect(content).not.toMatch(
                /export type User = \{.*?meta: unknown\[\]/,
            );
        });
    });
});
