<?php

namespace App\Enums;

enum ContentType: string
{
    case TikTokScript = 'tiktok_script';
    case FacebookPost = 'facebook_post';
    case MarketingCopy = 'marketing_copy';
    case SocialCaption = 'social_caption';
    case BlogPost = 'blog_post';
}
