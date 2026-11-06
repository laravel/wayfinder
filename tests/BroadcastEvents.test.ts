import Echo from "laravel-echo";
import { describe, expect, expectTypeOf, test, vi } from "vitest";
import type { App } from "../workbench/resources/js/wayfinder/types";
import { BroadcastEvents } from "../workbench/resources/js/wayfinder/broadcast-events";

describe("BroadcastEvents", () => {
    test("subscribes Echo to Laravel's fully qualified event name", () => {
        const bind = vi.fn();
        const echo = new Echo({
            broadcaster: "pusher",
            client: { subscribe: () => ({ bind }) },
            namespace: "Custom.Events",
            withoutInterceptors: true,
        });
        const listener = vi.fn();

        echo.channel("orders.1").listen(
            BroadcastEvents.App.Events.OrderShipped,
            listener,
        );

        expect(bind).toHaveBeenCalledWith("App\\Events\\OrderShipped", listener);
    });

    test("exports BroadcastEvents object", () => {
        expect(BroadcastEvents).toBeDefined();
        expect(typeof BroadcastEvents).toBe("object");
    });

    test("has nested App.Events structure", () => {
        expect(BroadcastEvents.App).toBeDefined();
        expect(BroadcastEvents.App.Events).toBeDefined();
    });

    test("has OrderShipped event in nested structure", () => {
        expect(BroadcastEvents.App.Events.OrderShipped).toBe(
            ".App\\Events\\OrderShipped",
        );
    });

    test("has UserNotification event in nested structure", () => {
        expect(BroadcastEvents.App.Events.UserNotification).toBe(
            ".App\\Events\\UserNotification",
        );
    });

    test("event names start with dot prefix", () => {
        const orderShipped = BroadcastEvents.App.Events.OrderShipped;
        expect(orderShipped).toMatch(/^\./);
        expect(orderShipped.split(".").length).toBeGreaterThan(1);
    });

    test("broadcastAs names are escaped as written", () => {
        expect(BroadcastEvents["server"]["provisioned"]).toBe(
            ".server.provisioned",
        );
    });

    test("abstract base events are left out", () => {
        expect(BroadcastEvents.App.Events).not.toHaveProperty("BaseServerEvent");
    });

    test("an inherited broadcastWith() is used when the event overrides nothing", () => {
        expectTypeOf<App.Events.ServerRebooted>().toEqualTypeOf<{
            event: string;
            data: unknown[];
        }>();
    });

    test("an inherited broadcastWith() promises nothing specific once the event overrides methods", () => {
        expectTypeOf<App.Events.ServerRenamed>().toEqualTypeOf<
            Record<string, unknown>
        >();
    });
});
