import { describe, expect, expectTypeOf, test } from "vitest";
import {
    PostStatus,
    Draft,
    Published,
    Archived,
} from "../workbench/resources/js/wayfinder/App/Enums/PostStatus";
import * as PostStatusModule from "../workbench/resources/js/wayfinder/App/Enums/PostStatus";
import {
    UnitEnum,
    UnitEnumMeta,
} from "../workbench/resources/js/wayfinder/App/Enums/UnitEnum";
import { EmptyEnum } from "../workbench/resources/js/wayfinder/App/Enums/EmptyEnum";
import * as EmptyEnumModule from "../workbench/resources/js/wayfinder/App/Enums/EmptyEnum";
import type { App } from "../workbench/resources/js/wayfinder/types";
import {
    ProductStatus,
    ProductStatusMeta,
    used,
    Active,
} from "../workbench/resources/js/wayfinder/App/Enums/ProductStatus";
import {
    OrderStatus,
    OrderStatusMeta,
} from "../workbench/resources/js/wayfinder/App/Enums/OrderStatus";
import {
    Priority,
    PriorityMeta,
} from "../workbench/resources/js/wayfinder/App/Enums/Priority";

describe("Enums", () => {
    test("exports enum constant object", () => {
        expect(PostStatus).toBeDefined();
        expect(typeof PostStatus).toBe("object");
    });

    test("has correct enum cases", () => {
        expect(PostStatus.Draft).toBe("draft");
        expect(PostStatus.Published).toBe("published");
        expect(PostStatus.Archived).toBe("archived");
    });

    test("has only expected keys", () => {
        expect(Object.keys(PostStatus)).toEqual([
            "Draft",
            "Published",
            "Archived",
        ]);
    });

    test("has correct enum cases with numeric values", () => {
        expect(UnitEnum.None).toBe(0);
        expect(UnitEnum.Open).toBe(1);
        expect(UnitEnum.Done).toBe(2);
    });

    test("namespaced unit enum type is a numeric union", () => {
        expectTypeOf<App.Enums.UnitEnum>().toEqualTypeOf<0 | 1 | 2>();
    });

    test("namespaced string-backed enum type is a string union", () => {
        expectTypeOf<App.Enums.PostStatus>().toEqualTypeOf<
            "draft" | "published" | "archived"
        >();
    });

    test("has only expected keys", () => {
        expect(Object.keys(UnitEnum)).toEqual(["None", "Open", "Done"]);
    });

    test("exports individual case constants", () => {
        expect(UnitEnum.None).toBe(0);
        expect(UnitEnum.Open).toBe(1);
        expect(UnitEnum.Done).toBe(2);
    });

    test("exports individual case constants", () => {
        expect(Draft).toBe("draft");
        expect(Published).toBe("published");
        expect(Archived).toBe("archived");
    });

    test("handles reserved keyword case names", () => {
        expect(ProductStatus.new).toBe("new");
        expect(ProductStatus.used).toBe("used");
        expect(ProductStatus.for).toBe("for-sale");
        expect(ProductStatus.Active).toBe("active");
    });

    test("exposes all keys for enums with reserved keyword cases", () => {
        expect(Object.keys(ProductStatus)).toEqual([
            "new",
            "used",
            "for",
            "Active",
        ]);
    });

    test("only exports non-reserved case constants individually", () => {
        expect(used).toBe("used");
        expect(Active).toBe("active");
    });

    test("enum without cases is an empty object", () => {
        expect(EmptyEnum).toEqual({});
        expect(Object.keys(EmptyEnum)).toEqual([]);
    });

    test("namespaced type for an enum without cases is never", () => {
        expectTypeOf<App.Enums.EmptyEnum>().toEqualTypeOf<never>();
    });
});

describe("Enum methods", () => {
    test("keys the meta object by case value", () => {
        expect(Object.keys(OrderStatusMeta)).toEqual([
            "pending",
            "shipped",
            "delivered",
            "cancelled",
        ]);
    });

    test("looks up meta with the case constant", () => {
        expect(OrderStatusMeta[OrderStatus.Delivered].label).toBe("Delivered");
    });

    test("resolves a method covering every case", () => {
        expect(OrderStatusMeta.pending.label).toBe("Awaiting Payment");
        expect(OrderStatusMeta.shipped.label).toBe("On Its Way");
        expect(OrderStatusMeta.cancelled.label).toBe("Cancelled");
    });

    test("resolves a method with a default match arm", () => {
        expect(OrderStatusMeta.pending.color).toBe("gray");
        expect(OrderStatusMeta.delivered.color).toBe("green");
        expect(OrderStatusMeta.cancelled.color).toBe("red");
    });

    test("keeps null returns", () => {
        expect(OrderStatusMeta.pending.description).toBeNull();
        expect(OrderStatusMeta.cancelled.description).toBe(
            'The customer said "no thanks" — see /docs/refunds.',
        );
    });

    test("resolves booleans and numbers", () => {
        expect(OrderStatusMeta.pending.isFinal).toBe(false);
        expect(OrderStatusMeta.delivered.isFinal).toBe(true);
        expect(OrderStatusMeta.pending.weight).toBe(1);
        expect(OrderStatusMeta.cancelled.weight).toBe(4);
    });

    test("resolves list and keyed arrays", () => {
        expect(OrderStatusMeta.shipped.tags).toEqual(["order", "shipped"]);
        expect(OrderStatusMeta.shipped.badge).toEqual({
            tone: "gray",
            text: "On Its Way",
        });
    });

    test("resolves a backed enum return to its value", () => {
        expect(OrderStatusMeta.pending.channel).toBe("database");
        expect(OrderStatusMeta.delivered.channel).toBe("mail");
    });

    test("resolves a collection return to an array", () => {
        expect(OrderStatusMeta.delivered.steps).toEqual([
            "placed",
            "delivered",
        ]);
    });

    test("resolves a stringable return to a string", () => {
        expect(OrderStatusMeta.delivered.heading).toBe("DELIVERED");
    });

    test("includes methods with only optional arguments", () => {
        expect(OrderStatusMeta.pending.slug).toBe("order-pending");
    });

    test("includes methods provided by a trait", () => {
        expect(OrderStatusMeta.pending.icon).toBe("icon-pending");
    });

    test("omits a method only for the cases it throws on", () => {
        expect("trackingUrl" in OrderStatusMeta.pending).toBe(false);
        expect(OrderStatusMeta.shipped.trackingUrl).toBe(
            "https://example.com/track/shipped",
        );
        expect(OrderStatusMeta.cancelled.trackingUrl).toBe(
            "https://example.com/track/cancelled",
        );
    });

    test("a method missing for a case is a type error", () => {
        // @ts-expect-error trackingUrl throws for the pending case
        expect(OrderStatusMeta.pending.trackingUrl).toBeUndefined();
    });

    test("drops a method that throws for every case", () => {
        expect(
            Object.values(OrderStatusMeta).some((meta) => "unavailable" in meta),
        ).toBe(false);
    });

    test("drops a method returning a value it cannot represent", () => {
        expect(
            Object.values(OrderStatusMeta).some((meta) => "payload" in meta),
        ).toBe(false);
        expect("stage" in PriorityMeta[Priority.Low]).toBe(false);
    });

    test("only includes eligible methods", () => {
        expect(Object.keys(OrderStatusMeta.shipped)).toEqual([
            "label",
            "color",
            "description",
            "isFinal",
            "weight",
            "tags",
            "badge",
            "channel",
            "steps",
            "heading",
            "trackingUrl",
            "slug",
            "icon",
        ]);
    });

    test("keys integer backed enums by their value", () => {
        expect(Object.keys(PriorityMeta)).toEqual(["1", "2"]);
        expect(PriorityMeta[Priority.Low].label).toBe("Low");
        expect(PriorityMeta[Priority.High].multiplier).toBe(2.5);
    });

    test("keys unit enums by their generated index", () => {
        expect(Object.keys(UnitEnumMeta)).toEqual(["0", "1", "2"]);
        expect(UnitEnumMeta[UnitEnum.None].label).toBe("Not Started");
        expect(UnitEnumMeta[UnitEnum.Done].label).toBe("Finished");
    });

    test("handles case values that are not valid identifiers", () => {
        expect(ProductStatusMeta["for-sale"].label).toBe("For Sale");
        expect(ProductStatusMeta[ProductStatus.for].label).toBe("For Sale");
    });

    test("handles method names that are reserved keywords", () => {
        expect(ProductStatusMeta.active.default).toBe(true);
        expect(ProductStatusMeta.new.default).toBe(false);
    });

    test("emits nothing for an enum without methods", () => {
        expect("PostStatusMeta" in PostStatusModule).toBe(false);
        expect(PostStatus.Draft).toBe("draft");
    });

    test("emits nothing for an enum without cases", () => {
        expect("EmptyEnumMeta" in EmptyEnumModule).toBe(false);
        expect(EmptyEnum).toEqual({});
    });

    test("infers literal types for resolved values", () => {
        expectTypeOf(
            OrderStatusMeta[OrderStatus.Delivered].label,
        ).toEqualTypeOf<"Delivered">();
        expectTypeOf(
            PriorityMeta[Priority.Low].multiplier,
        ).toEqualTypeOf<0.5>();
    });
});
