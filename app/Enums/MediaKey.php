<?php

declare(strict_types=1);

namespace App\Enums;

use TheSoftPulze\LaravibeStandards\Enums\Concerns\HasEnumMetadata;

enum MediaKey: string
{
    use HasEnumMetadata;

    case Portrait640 = 'portrait_640';
    case Portrait960 = 'portrait_960';
    case Avatar = 'avatar';
    case Photo = 'photo';
    case ShareImage = 'share_image';
    case FaviconIco = 'favicon_ico';
    case FaviconPng = 'favicon_png';
    case AppleTouchIcon = 'apple_touch_icon';
    case ResumePdf = 'resume_pdf';
}
