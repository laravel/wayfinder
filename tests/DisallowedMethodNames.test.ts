import { expect, test } from "vitest";
import DisallowedMethodNameController, {
    deleteMethod,
    method404,
} from "../workbench/resources/js/actions/App/Http/Controllers/DisallowedMethodNameController";
import method2fa from "../workbench/resources/js/routes/2fa";
import defaultMethod from "../workbench/resources/js/routes/default";
import disallowed from "../workbench/resources/js/routes/disallowed";

test("will append `method` to invalid methods", () => {
    expect(method404.url()).toBe("/disallowed/404");
    expect(deleteMethod.url()).toBe("/disallowed/delete");
    expect(DisallowedMethodNameController.delete.url()).toBe(
        "/disallowed/delete",
    );
    expect(DisallowedMethodNameController[404].url()).toBe("/disallowed/404");
});

test("will append `method` to invalid methods", () => {
    expect(disallowed[404].url()).toBe("/disallowed/404");
});

test("will properly handle leading numbers", () => {
    expect(method2fa.disallowed.url()).toBe("/disallowed/2fa");
    expect(DisallowedMethodNameController["2fa"].url()).toBe("/disallowed/2fa");
});

test("will properly handle reserved JS words", () => {
    expect(defaultMethod.login.url()).toBe("/disallowed/default");
    expect(DisallowedMethodNameController["default"].url()).toBe(
        "/disallowed/default",
    );
});
