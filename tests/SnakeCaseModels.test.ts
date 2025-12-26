import { readFileSync } from "fs";
import { join } from "path";
import { describe, expect, test } from "vitest";

/**
 * These tests verify the snake_case conversion behavior for model properties.
 *
 * The Models converter in `src/Converters/Models.php` converts property keys to snake_case
 * when `snakeCaseAttributes()` returns true (which is the default in Laravel).
 *
 * This behavior ensures that model property names match Laravel's default
 * attribute serialization format (snake_case) in the generated TypeScript types.
 */
describe("Snake Case Model Properties", () => {
    const typesPath = join(
        __dirname,
        "../workbench/resources/js/wayfinder/types.d.ts",
    );
    const content = readFileSync(typesPath, "utf-8");

    test("types.d.ts file is generated", () => {
        expect(content).toBeDefined();
        expect(content.length).toBeGreaterThan(0);
    });

    describe("User model", () => {
        test("User type exists in App.Models namespace", () => {
            expect(content).toContain("export namespace App");
            expect(content).toContain("export namespace Models");
            expect(content).toContain("export type User");
        });

        test("User type has snake_case relation names", () => {
            // Relations with camelCase method names should be converted to snake_case
            // ownedProducts -> owned_products
            // favoriteCategories -> favorite_categories
            // notifications is already lowercase
            expect(content).toMatch(/export type User = \{[^}]*owned_products/);
            expect(content).toMatch(
                /export type User = \{[^}]*favorite_categories/,
            );
            expect(content).toMatch(/export type User = \{[^}]*notifications/);
        });

        test("User relation type references correct namespaced type", () => {
            expect(content).toContain(
                "Illuminate.Notifications.DatabaseNotification",
            );
        });
    });

    describe("DatabaseNotification model", () => {
        test("DatabaseNotification type exists in Illuminate.Notifications namespace", () => {
            expect(content).toContain("export namespace Illuminate");
            expect(content).toContain("export namespace Notifications");
            expect(content).toContain("export type DatabaseNotification");
        });

        test("DatabaseNotification has snake_case property names", () => {
            // 'incrementing' is already lowercase/snake_case
            expect(content).toMatch(
                /export type DatabaseNotification = \{[^}]*incrementing: boolean[^}]*\}/,
            );
        });
    });

    describe("snake_case conversion rules", () => {
        test("property names should use snake_case format (not camelCase)", () => {
            // Model types should not have camelCase property names like 'isActive', 'createdAt', etc.
            // They should be converted to 'is_active', 'created_at', etc.
            const modelTypePattern =
                /export type (User|DatabaseNotification) = \{([^}]+)\}/g;
            let match;

            while ((match = modelTypePattern.exec(content)) !== null) {
                const typeName = match[1];
                const typeBody = match[2];

                // Extract property names from the type body
                const propertyPattern = /(\w+):/g;
                let propMatch;
                while ((propMatch = propertyPattern.exec(typeBody)) !== null) {
                    const propName = propMatch[1];

                    // Check that property names follow snake_case convention
                    // snake_case properties should not have uppercase letters in the middle
                    // Exception: properties that are already single lowercase words are fine
                    if (propName.length > 1) {
                        // If it has uppercase letters in the middle, it's camelCase - which is wrong for models
                        const hasCamelCase = /[a-z][A-Z]/.test(propName);
                        expect(
                            hasCamelCase,
                            `Model ${typeName} property '${propName}' should be snake_case, not camelCase`,
                        ).toBe(false);
                    }
                }
            }
        });

        test("events retain camelCase property names (not snake_cased)", () => {
            // Events go through a different converter and should NOT have snake_case applied
            // This verifies the distinction between Models (snake_case) and Events (camelCase)
            expect(content).toMatch(
                /export type UserNotification = \{[^}]*userId:/,
            );
            expect(content).toMatch(
                /export type OrderShipped = \{[^}]*orderId:[^}]*trackingNumber:/,
            );
        });
    });

    describe("model namespace structure", () => {
        test("App.Models namespace contains model types", () => {
            // Model types should be in App.Models namespace
            const appModelsPattern =
                /export namespace App \{[\s\S]*?export namespace Models \{[\s\S]*?export type (\w+)/;
            expect(content).toMatch(appModelsPattern);
        });

        test("Illuminate.Notifications namespace contains framework model types", () => {
            // Framework model types should be in their respective namespaces
            const illuminatePattern =
                /export namespace Illuminate \{[\s\S]*?export namespace Notifications \{[\s\S]*?export type DatabaseNotification/;
            expect(content).toMatch(illuminatePattern);
        });
    });

    describe("camelCase to snake_case conversion verification", () => {
        test("camelCase relation names are converted to snake_case", () => {
            // Verify that camelCase relation method names get converted to snake_case
            // ownedProducts() in PHP -> owned_products in TypeScript
            expect(content).toContain("owned_products");
            expect(content).not.toMatch(
                /export type User = \{[^}]*ownedProducts/,
            );

            // favoriteCategories() in PHP -> favorite_categories in TypeScript
            expect(content).toContain("favorite_categories");
            expect(content).not.toMatch(
                /export type User = \{[^}]*favoriteCategories/,
            );
        });

        test("relation types reference correct model namespaces", () => {
            // owned_products should reference App.Models.Product
            expect(content).toMatch(
                /owned_products\??:\s*App\.Models\.Product/,
            );

            // favorite_categories should reference App.Models.Category
            expect(content).toMatch(
                /favorite_categories\??:\s*App\.Models\.Category/,
            );
        });

        test("Product model has snake_case relations", () => {
            // productCategory() -> product_category
            expect(content).toMatch(
                /export type Product = \{[^}]*product_category/,
            );
            expect(content).not.toMatch(
                /export type Product = \{[^}]*productCategory/,
            );

            // relatedProducts() -> related_products
            expect(content).toMatch(
                /export type Product = \{[^}]*related_products/,
            );
            expect(content).not.toMatch(
                /export type Product = \{[^}]*relatedProducts/,
            );
        });

        test("Category model respects snakeAttributes = false (camelCase)", () => {
            // Category model has $snakeAttributes = false, so it should retain camelCase
            // categoryProducts() stays as categoryProducts (NOT converted to category_products)
            // subCategories() stays as subCategories (NOT converted to sub_categories)
            expect(content).toMatch(
                /export type Category = \{[^}]*categoryProducts/,
            );
            expect(content).toMatch(
                /export type Category = \{[^}]*subCategories/,
            );

            // Verify the snake_case versions are NOT present
            expect(content).not.toMatch(
                /export type Category = \{[^}]*category_products/,
            );
            expect(content).not.toMatch(
                /export type Category = \{[^}]*sub_categories/,
            );
        });
    });
});

describe("Events vs Models property format comparison", () => {
    const typesPath = join(
        __dirname,
        "../workbench/resources/js/wayfinder/types.d.ts",
    );
    const content = readFileSync(typesPath, "utf-8");

    test("Models use snake_case while Events use camelCase", () => {
        // Extract User model type
        const userModelMatch = content.match(/export type User = \{([^}]+)\}/);
        expect(userModelMatch).not.toBeNull();

        // Extract UserNotification event type
        const userNotificationMatch = content.match(
            /export type UserNotification = \{([^}]+)\}/,
        );
        expect(userNotificationMatch).not.toBeNull();

        if (userModelMatch && userNotificationMatch) {
            // Model property NAMES should be snake_case (no camelCase in property names)
            // We need to extract just the property names, not the type references
            const modelProps = userModelMatch[1];
            const propertyNames = modelProps.match(/(\w+)\??:/g) || [];

            // Verify property names are snake_case (no camelCase pattern in property names)
            propertyNames.forEach((prop) => {
                const propName = prop.replace(/\??:$/, "");
                expect(
                    /[a-z][A-Z]/.test(propName),
                    `Property name '${propName}' should be snake_case`,
                ).toBe(false);
            });

            // Event properties should be camelCase
            const eventProps = userNotificationMatch[1];
            expect(eventProps).toContain("userId");
        }
    });

    test("OrderShipped event has camelCase properties", () => {
        expect(content).toMatch(
            /export type OrderShipped = \{[^}]*orderId:[^}]*trackingNumber:/,
        );
    });
});
