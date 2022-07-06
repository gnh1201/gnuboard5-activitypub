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
    "id": "http://utilhome.dothome.co.kr/bbs/board.php?bo_table=apstreams#Draft",
    "to": ["https://www.w3.org/ns/activitystreams#Public", "http://utilhome.dothome.co.kr/?route=activitypub.user&mb_id=admin"],
    "actor": "http://utilhome.dothome.co.kr/?route=activitypub.user&mb_id=admin",
    "object": {
        "type": "Note",
        "generator": "GNUBOARD5 ActivityPub Plugin (INSTANCE_ID: 4d6076784cbd864ade7c746690d37051, INSTANCE_VERSION: 0.1.10-dev)",
        "id": "http://utilhome.dothome.co.kr/bbs/board.php?bo_table=apstreams&wr_id=193",
        "attributedTo": "http://utilhome.dothome.co.kr/?route=activitypub.user&mb_id=admin",
        "content": "날씨 어때요? @admin@utilhome.dothome.co.kr",
        "icon": "https://www.gravatar.com/avatar/bdbd5eb70305f1eaaa0340687758676a",
        "location": {
            "name": "xxx.xxx.xxx.xxx, 서울특별시 구로구 구로5동 (DLIVE), Seoul, Seoul-teukbyeolsi, Korea (Republic of), KR, 06030, +09:00",
            "type": "Place",
            "longitude": 126.8892945,
            "latitude": 37.5001593,
            "units": "m",
            "_openweathermap_current": {
                "dt": 1657094765,
                "sunrise": 1657052227,
                "sunset": 1657105004,
                "temp": 307.05,
                "feels_like": 311.66,
                "pressure": 1005,
                "humidity": 51,
                "dew_point": 295.49,
                "uvi": 2.14,
                "clouds": 100,
                "visibility": 10000,
                "wind_speed": 3,
                "wind_deg": 139,
                "wind_gust": 4.21,
                "weather": [{
                    "id": 804,
                    "main": "Clouds",
                    "description": "overcast clouds",
                    "icon": "04d"
                }]
            }
        }
    },
    "published": "2022-07-06T08:07:01Z",
    "updated": "2022-07-06T08:07:01Z"
}
```

## 문의
* gnh1201@gmail.com
