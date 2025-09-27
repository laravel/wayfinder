import { expect, it, beforeEach } from "vitest";
import { currentRoute } from "../workbench/resources/js/wayfinder";

const mockWindow = {
    location: {
        href: "",
    },
};

Object.defineProperty(global, "window", {
    value: mockWindow,
    writable: true,
});

const baseUrl = () => {
    const appUrl = process.env.VITE_APP_URL || "https://wayfinder.test";
    return appUrl.replace(/\/$/, "");
};

const setHref = (href: string) => {
    const base = baseUrl();
    // console.log(base + decodeURIComponent(href));
    mockWindow.location.href = base + href;
};

it("returns current URL when no args", () => {
    setHref("/current/page");
    expect(currentRoute()).toBe(baseUrl() + "/current/page");
});

it("matches exact route without params", () => {
    setHref("/posts");
    expect(currentRoute("posts.index")).toBe(true);
});

it("matches route with primitive param", () => {
    setHref("/posts/123");
    expect(currentRoute("posts.show", 123)).toBe(true);
    expect(currentRoute("posts.show")).toBe(true);
});

it("matches route with object param", () => {
    setHref("/posts/123");
    expect(currentRoute("posts.show", { post: 123 })).toBe(true);
});

it("mismatches when param value differs", () => {
    setHref("/posts/123");
    expect(currentRoute("posts.show", "456")).toBe(false);
});

it("matches with query params", () => {
    setHref("/posts?page=2&sort=name");
    expect(currentRoute("posts.index", { page: 2, sort: "name" })).toBe(true);
});

it("matches with nested query params", () => {
    setHref(
        "/posts/123?meta[author]=john&meta[status]=draft&settings[theme]=dark",
    );
    expect(
        currentRoute("posts.show", {
            post: 123,
            meta: { author: "john", status: "draft" },
            settings: { theme: "dark" },
        }),
    ).toBe(true);
});

it("mismatches with wrong nested query value", () => {
    setHref("/posts/123?meta[author]=john&meta[status]=draft");
    expect(
        currentRoute("posts.show", {
            post: 123,
            meta: { author: "jane", status: "draft" },
        }),
    ).toBe(false);
});

it("supports wildcard prefix routes", () => {
    setHref("/posts/123/edit");
    expect(currentRoute("posts.*")).toBe(true);
});

it("supports wildcard suffix routes", () => {
    setHref("/create/post");
    expect(currentRoute("*.post")).toBe(true);
});

it("normalizes trailing slashes", () => {
    setHref("/dashboard/");
    expect(currentRoute("dashboard")).toBe(true);
});

it("returns false for different route", () => {
    setHref("/posts");
    expect(currentRoute("home")).toBe(false);
});

it("matches encoded URI path segment against placeholder without params", () => {
    // Route template is /posts/{post}
    setHref("/posts/some%20slug");
    expect(currentRoute("posts.show")).toBe(true);
});

it("matches encoded query params correctly", () => {
    // Ensure query param comparison decodes current URL values
    setHref(
        "/posts?page=2&search=hello%20world&tags%5B%5D=a%20b&tags%5B%5D=12",
    );
    expect(
        currentRoute("posts.index", {
            page: 2,
            search: "hello world",
            tags: ["a b", 12],
        }),
    ).toBe(true);
});

it("matches pdf.download with encoded filename in URL", () => {
    setHref("/download/42/my%2Dfile(1).pdf");
    expect(
        currentRoute("pdf.download", {
            pdfId: 42,
        }),
    ).toBe(true);
});

it("matches pdf.download without optional fileName in URL", () => {
    setHref("/download/42");
    expect(currentRoute("pdf.download", { pdfId: 42 })).toBe(true);
});

it("matches pdf.download with fileName in check but not in URL", () => {
    setHref("/download/42");
    expect(
        currentRoute("pdf.download", {
            pdfId: 42,
            fileName: "my-file(1).pdf",
        }),
    ).toBe(false);
});

it("matches pdf.download with fileName in both URL and check", () => {
    setHref("/download/42/my%2Dfile%281%29.pdf");
    expect(
        currentRoute("pdf.download", {
            pdfId: 42,
            fileName: "my-file(1).pdf",
        }),
    ).toBe(true);

    setHref("/download/42/my%2Dfile(1).pdf");
    expect(
        currentRoute("pdf.download", {
            pdfId: 42,
            fileName: "my-file(1).pdf",
        }),
    ).toBe(true);
});

it("treats array parameters as query parameters, not route parameters", () => {
    setHref("/posts/12");
    expect(
        currentRoute("posts.show", {
            post: ["12"],
        }),
    ).toBe(false);
});

it("handles array parameters correctly in query string", () => {
    setHref("/posts/12?slug[]=12&slug[]=34");
    expect(
        currentRoute("posts.show", {
            slug: ["12", "34"],
        }),
    ).toBe(true);
});

it("handles indexed array parameters in query string", () => {
    setHref("/posts/12?nice[]=a&nice[]=12");
    expect(
        currentRoute("posts.show", {
            nice: ["a", "12"], // Should handle nice[0], nice[1] format
        }),
    ).toBe(true);
});

it("handles mixed array parameter formats", () => {
    setHref(
        "/posts/12?tags[]=red&tags[]=blue&categories[0]=tech&categories[1]=news",
    );
    expect(
        currentRoute("posts.show", {
            tags: ["red", "blue"],
            categories: ["tech", "news"],
        }),
    ).toBe(true);
});
