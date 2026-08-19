import { describe, expect, expectTypeOf, test } from "vitest";
import {
    QuotedEnum,
    QuotedEnumMeta,
    Double,
    Newline,
} from "../workbench/resources/js/wayfinder/App/Enums/QuotedEnum";
import { quotedDefaults } from "../workbench/resources/js/wayfinder/App/Http/Controllers/UrlDefaultsController";
import { BroadcastChannels } from "../workbench/resources/js/wayfinder/broadcast-channels";
import type { BroadcastChannel } from "../workbench/resources/js/wayfinder/broadcast-channels";
import type { App } from "../workbench/resources/js/wayfinder/types";

describe("EscapedValues", () => {
    test("enum values keep their quotes, backslashes and newlines", () => {
        expect(QuotedEnum.Double).toBe('say "hi"');
        expect(QuotedEnum.Single).toBe("it's here");
        expect(QuotedEnum.Backslash).toBe("back\\slash");
        expect(QuotedEnum.Newline).toBe("line\nbreak");
    });

    test("exported enum case constants keep their quotes", () => {
        expect(Double).toBe('say "hi"');
        expect(Newline).toBe("line\nbreak");
    });

    test("enum meta is keyed by the escaped case value", () => {
        expect(QuotedEnumMeta['say "hi"'].label).toBe('A "quoted" label');
        expect(QuotedEnumMeta["line\nbreak"].label).toBe("multi\nline");
        expect(QuotedEnumMeta[QuotedEnum.Backslash].label).toBe(
            "back\\slash label",
        );
    });

    test("enum type union escapes case values", () => {
        expectTypeOf<App.Enums.QuotedEnum>().toEqualTypeOf<
            'say "hi"' | "it's here" | "back\\slash" | "line\nbreak"
        >();
    });

    test("validation in: values are escaped in the request type", () => {
        expectTypeOf<
            App.Http.Controllers.PostController.Store.Request["status"]
        >().toEqualTypeOf<
            'say "hi"' | "it's" | "back\\slash" | null | undefined
        >();
    });

    test("channel names with backticks and dollars are escaped", () => {
        expect(BroadcastChannels.quirky["`tick`-$dollar"](7)).toBe(
            "quirky.`tick`-$dollar.7",
        );

        expectTypeOf<`quirky.\`tick\`-$dollar.${string}`>().toMatchTypeOf<BroadcastChannel>();
    });

    test("url defaults containing quotes are escaped", () => {
        expect(quotedDefaults.url()).toBe('/with-quoted-defaults/say "hi" now');
    });
});
