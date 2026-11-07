import { expect, test } from "vitest";
import {
    index,
    show,
    store,
    search,
    filters,
    required,
} from "../workbench/resources/js/wayfinder/routes/orders";
import { index as controllerIndex } from "../workbench/resources/js/wayfinder/App/Http/Controllers/OrderController";
import { index as posts } from "../workbench/resources/js/wayfinder/App/Http/Controllers/PostController";

test("validated GET helpers serialize typed filters without changing URLs", () => {
    expect(
        index.url({
            query: {
                status: "paid",
                sort: "created_at",
                page: 2,
                archived: false,
            },
        }),
    ).toBe("/orders?status=paid&sort=created_at&page=2&archived=0");
    expect(index.get({ query: { status: "shipped" } }).method).toBe("get");
    expect(index.head({ query: { page: 0 } }).method).toBe("head");
    expect(index.form.head({ query: { status: "paid" } })).toEqual({
        action: "/orders?_method=HEAD&status=paid",
        method: "get",
    });
    expect(show.url(7, { query: { status: "paid" } })).toBe(
        "/orders/7?status=paid",
    );
    expect(controllerIndex["/orders/archive"].url({ query: { page: 2 } })).toBe(
        "/orders/archive?page=2",
    );
});

test("mergeQuery supports partial updates and filter removal", () => {
    window.location.search = "?status=paid&sort=total&page=2";
    expect(
        index.url({ mergeQuery: { page: 3, status: null, sort: undefined } }),
    ).toBe("/orders?page=3");
    expect(required.url({ mergeQuery: { page: 4 } })).toBe(
        "/order-report?status=paid&sort=total&page=4",
    );
    window.location.search = "";
});

test("unvalidated, write, mixed-verb and unsupported routes retain generic queries", () => {
    expect(posts.url({ query: { campaign: "summer" } })).toContain(
        "campaign=summer",
    );
    expect(
        store.url({ query: { status: "custom", campaign: "summer" } }),
    ).toContain("campaign=summer");
    expect(search.post({ query: { campaign: "summer" } }).method).toBe("post");
    expect(filters.url({ query: { filters: { archived: true } } })).toBe(
        "/order-filters?filters%5Barchived%5D=1",
    );
});

// These calls are checked by tsc, not executed by Vitest.
function checkTypes() {
    // @ts-expect-error invalid status value
    index({ query: { status: "payed" } });
    // @ts-expect-error invalid sort field
    index.url({ query: { sort: "creation_date" } });
    // @ts-expect-error numeric pagination
    index.get({ query: { page: "two" } });
    // @ts-expect-error misspelled filter key
    index.head({ query: { stats: "paid" } });
    // @ts-expect-error form helpers share the query type
    index.form({ query: { status: "payed" } });
    // @ts-expect-error mergeQuery preserves allowed values
    index({ mergeQuery: { status: "payed" } });
    // @ts-expect-error controller dictionary entries also carry query types
    controllerIndex["/orders"]({ query: { page: "two" } });
    // @ts-expect-error URL parameters do not replace query typing
    show(7, { query: { status: "payed" } });
    // @ts-expect-error required fields remain required when replacing the query
    required({ query: { page: 2 } });
    // @ts-expect-error component helpers preserve query values
    index.withComponent({ query: { status: "payed" } });
    // @ts-expect-error component form helpers preserve query values
    index.form.withComponent({ query: { page: "two" } });
}
void checkTypes;

test.runIf(Boolean(process.env.WAYFINDER_GENERATE_INERTIA_COMPONENT))(
    "component variants retain typed queries",
    () => {
        expect(index.withComponent({ query: { status: "paid" } })).toEqual({
            url: "/orders?status=paid",
            method: "get",
            component: "Orders/Index",
        });
        expect(index.form.withComponent({ query: { page: 2 } })).toEqual({
            action: "/orders?page=2",
            method: "get",
            component: "Orders/Index",
        });
    },
);
