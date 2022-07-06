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
- [x] (Added) Geolocation
- [ ] (Added) File attachment

## 전문 예시

```json
{
    "@context": "https://www.w3.org/ns/activitystreams",
    "type": "Create",
    "id": "http://example.org/bbs/board.php?bo_table=apstreams#Draft",
    "to": ["https://www.w3.org/ns/activitystreams#Public", "http://example.org/?route=activitypub.user&mb_id=admin"],
    "actor": "http://example.org/?route=activitypub.user&mb_id=admin",
    "object": {
        "type": "Note",
        "generator": "GNUBOARD5 ActivityPub Plugin (INSTANCE_ID: 4d6076784cbd864ade7c746690d37051, INSTANCE_VERSION: 0.1.10-dev)",
        "id": "http://example.org/bbs/board.php?bo_table=apstreams&wr_id=183",
        "attributedTo": "http://example.org/?route=activitypub.user&mb_id=admin",
        "content": "hello world @admin@example.org",
        "icon": "https://www.gravatar.com/avatar/bdbd5eb70305f1eaaa0340687758676a",
        "location": {
            "name": "xxx.xxx.xxx.xxx, 인천광역시 남동구 구월3동 (SK Broadband Co Ltd), Seoul, Seoul-teukbyeolsi, Korea (Republic of), KR, 06030, +09:00",
            "type": "Place",
            "longitude": 126.6969053,
            "latitude": 37.4527115,
            "units": "m"
        }
    },
    "published": "2022-07-06T05:06:26Z",
    "updated": "2022-07-06T05:06:26Z"
}
```

## 문의
* gnh1201@gmail.com
