# gnuboard5-activitypub
ActivityPub implementation for GNUBOARD 5

## References
* https://www.w3.org/TR/activitypub/
* https://www.w3.org/TR/activitystreams-core/
* https://www.w3.org/TR/activitystreams-vocabulary/
* https://github.com/w3c/activitypub/issues/194
* https://docs.joinmastodon.org/spec/webfinger/
* https://organicdesign.nz/ActivityPub_Code
* https://socialhub.activitypub.rocks/t/posting-to-pleroma-inbox/1184
* https://github.com/broidHQ/integrations/tree/master/broid-schemas#readme

## 사용 전 설정
  * `apstreams` 게시판 추가
  * `apstreams` 사용자 추가

## 작업진행
- [x] WebFinger
- [x] User
- [x] Inbox
- [x] Outbox
- [x] Followers
- [x] Following
- [x] Liked

## 전문 예시

```json
{
    "@context": "https://www.w3.org/ns/activitystreams",
    "type": "Create",
    "id": "http://website.local/bbs/board.php?bo_table=apstreams#Draft",
    "to": ["https://www.w3.org/ns/activitystreams#Public", "http://website.local/?route=activitypub.user&mb_id=admin"],
    "actor": "http://website.local/?route=activitypub.user&mb_id=admin",
    "object": {
        "type": "Note",
        "generator": "GNUBOARD5 ActivityPub Plugin (INSTANCE_ID: 4d6076784cbd864ade7c746690d37051, INSTANCE_VERSION: 0.1.10-dev)",
        "id": "http://website.local/bbs/board.php?bo_table=apstreams&wr_id=156",
        "attributedTo": "http://website.local/?route=activitypub.user&mb_id=admin",
        "content": "hello world @admin@website.local",
        "icon": "https://www.gravatar.com/avatar/bdbd5eb70305f1eaaa0340687758676a",
        "location": {
            "name": "xxx.xxx.xxx.xxx, Seoul, Seoul-teukbyeolsi, Korea (Republic of), KR, 06030, +09:00",
            "type": "Place",
            "longitude": 126.977943,
            "latitude": 37.566311,
            "units": "m"
        }
    },
    "published": "2022-07-05T09:37:06Z",
    "updated": "2022-07-05T09:37:06Z"
}
```

## 문의
* gnh1201@gmail.com
