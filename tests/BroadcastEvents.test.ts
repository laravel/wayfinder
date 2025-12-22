import { describe, expect, test } from "vitest";
import { BroadcastEvents } from "../workbench/resources/js/wayfinder/broadcast-events";

describe("BroadcastEvents", () => {
    test("exports BroadcastEvents object", () => {
        expect(BroadcastEvents).toBeDefined();
        expect(typeof BroadcastEvents).toBe("object");
    });

    test("has OrderShipped event with dot notation key", () => {
        expect(BroadcastEvents["App.Events.OrderShipped"]).toBe(
            ".App.Events.OrderShipped"
        );
    });

    test("has UserNotification event with dot notation key", () => {
        expect(BroadcastEvents["App.Events.UserNotification"]).toBe(
            ".App.Events.UserNotification"
        );
    });

    test("event names start with dot prefix", () => {
        const orderShipped = BroadcastEvents["App.Events.OrderShipped"];
        expect(orderShipped).toMatch(/^\./);
        expect(orderShipped.split(".").length).toBeGreaterThan(1);
    });
});
