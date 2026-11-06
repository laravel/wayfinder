import { expect, it } from "vitest";
import albums from "../workbench/resources/js/wayfinder/routes/albums";
import photos from "../workbench/resources/js/wayfinder/routes/photos";

it("can handle a route name that is both a leaf and a prefix of 'index'", () => {
    expect(photos.index().url).toBe("/photos");
    expect(photos.index.window().url).toBe("/photos/window");
});

it("can handle an 'index' prefix with no leaf route of its own", () => {
    expect(albums.index.recent().url).toBe("/albums/recent");
});
