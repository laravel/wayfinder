import { expect, it } from "vitest";
import api from "../workbench/resources/js/routes/api";
import v1 from "../workbench/resources/js/routes/api/v1";

it("can normalize to camel case", () => {
    expect(v1.taskStatus.index(1).url).toBe("/api/v1/tasks/1/task-status");
    expect(v1.taskStatus.index(1)).toEqual({
        url: "/api/v1/tasks/1/task-status",
        method: "get",
    });
});

it("will properly export barrel files", () => {
    expect(api.v1.tasks.url()).toBe("/api/v1/tasks");
    expect(api.v1.tasks()).toEqual({
        url: "/api/v1/tasks",
        method: "get",
    });

    expect(api.v1.taskStatus.index(1).url).toBe("/api/v1/tasks/1/task-status");
    expect(api.v1.taskStatus.index(1)).toEqual({
        url: "/api/v1/tasks/1/task-status",
        method: "get",
    });
});
