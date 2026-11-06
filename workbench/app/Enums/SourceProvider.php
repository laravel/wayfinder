<?php

namespace App\Enums;

use Laravel\Wayfinder\Attributes\WayfinderIgnore;

enum SourceProvider: string
{
    case Github = 'github';

    case Gitlab = 'gitlab';

    #[WayfinderIgnore(unless: 'features.fake_source_provider')]
    case GitFake = 'gitfake';

    #[WayfinderIgnore(when: 'features.hide_retired_source_provider')]
    case GitRetired = 'gitretired';
}
