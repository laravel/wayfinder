import { expect, it } from "vitest";
import v1 from "../workbench/resources/js/routes/api/v1/index";

it('can normalize to camel case', () => {
    expect(v1.taskStatus.index(1).url).toBe("/api/v1/tasks/1/task-status");
    expect(v1.taskStatus.index(1)).toEqual({
        url: "/api/v1/tasks/1/task-status",
        method: "get",
    });
});
