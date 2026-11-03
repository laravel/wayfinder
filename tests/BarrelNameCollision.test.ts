import { expect, it } from "vitest";
import download from "../workbench/resources/js/wayfinder/routes/backups/download";

it("keeps a route whose name matches its group's default export", () => {
    expect(download.show({ backup: 1 }).url).toBe("/backups/1/download");
    expect(download.download({ backup: 1 })).toEqual({
        url: "/backups/1/download",
        method: "post",
    });
});
