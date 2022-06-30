<?php
if (!defined('_GNUBOARD_')) exit;

return <<<EOF
{
    "self": {
        "enabled: true,
        "platform": "gnuboard5",
        "user": "/?route=activitypub.user&mb_id=:username",
        "inbox": "/?route=activitypub.inbox",
        "userinbox": "/?route=activitypub.inbox&mb_id=:username",
        "docs": [
            "https://github.com/gnh1201/gnuboard5-activitypub"
        ]
    },
    "https://mastodon.social": {
        "enabled": ture,
        "platform": "mastodon",
        "user": "/users/:username",
        "inbox": "/inbox",
        "userinbox": "/user/inbox/:username/inbox",
        "accesstoken": "YOUR ACCESSTOKEN HERE",
        "docs": [
            "https://docs.joinmastodon.org/spec/activitypub/"
        ]
    },
    "https://peertube.local": {
        "enabled": false,
        "platform": "peertube",
        "user": "/accounts/:username",
        "inbox": "/inbox",
        "userinbox": "/accounts/:username/inbox",
        "docs": [
            "https://docs.joinpeertube.org/api-rest-reference.html"
        ]
    }
}
EOF;
