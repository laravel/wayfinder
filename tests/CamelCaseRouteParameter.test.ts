import { expect, test } from "vitest";
import { show } from "../workbench/resources/js/actions/App/Http/Controllers/AuditEntryController";

test("can resolve model binding keys for camelCase route handler parameters", () => {
    expect(show.url({ audit_entry: { id: 1 } })).toBe("/audit-entries/1");
    expect(show.url({ audit_entry: 1 })).toBe("/audit-entries/1");
    expect(show.url({ id: 1 })).toBe("/audit-entries/1");
    expect(show.url([1])).toBe("/audit-entries/1");
    expect(show.url(1)).toBe("/audit-entries/1");
    expect(show.url("some-slug")).toBe("/audit-entries/some-slug");
});
