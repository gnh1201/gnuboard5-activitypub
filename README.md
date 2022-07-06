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

## 부가기능 (옵션)
- [x] 날씨 (openweathermap.org)
- [x] 환율 (koreaexim.go.kr)

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
        "generator": "GNUBOARD5 ActivityPub Plugin (INSTANCE_ID: 4d6076784cbd864ade7c746690d37051, INSTANCE_VERSION: 0.1.11-dev)",
        "id": "http://example.org/bbs/board.php?bo_table=apstreams&wr_id=221",
        "attributedTo": "http://example.org/?route=activitypub.user&mb_id=admin",
        "content": "hello, world @admin@example.org",
        "icon": "https://www.gravatar.com/avatar/bdbd5eb70305f1eaaa0340687758676a",
        "location": {
            "name": "xxx.xxx.xxx.xxx, 서울특별시 구로구 구로5동 (DLIVE), Seoul, Seoul-teukbyeolsi, Korea (Republic of), KR, 06030, +09:00",
            "type": "Place",
            "longitude": 126.8892945,
            "latitude": 37.5001593,
            "units": "m",
            "_weather": {
                "dt": 1657103776,
                "sunrise": 1657052227,
                "sunset": 1657105004,
                "temp": 305.05,
                "feels_like": 310.6,
                "pressure": 1005,
                "humidity": 62,
                "dew_point": 296.86,
                "uvi": 0,
                "clouds": 75,
                "visibility": 10000,
                "wind_speed": 3.09,
                "wind_deg": 150,
                "weather": [{
                    "id": 803,
                    "main": "Clouds",
                    "description": "broken clouds",
                    "icon": "04d"
                }]
            },
            "_exchange": {
                "KRW": {
                    "AED": 353.38,
                    "AUD": 882.25,
                    "BHD": 3442.79,
                    "BND": 923.71,
                    "CAD": 996.28,
                    "CHF": 1340.42,
                    "CNH": 194.16,
                    "DKK": 179.12,
                    "EUR": 1332.72,
                    "GBP": 1551.95,
                    "HKD": 165.42,
                    "IDR(100)": 8.66,
                    "JPY(100)": 957.05,
                    "KRW": 0,
                    "KWD": 2135.85,
                    "MYR": 293.7,
                    "NOK": 129.06,
                    "NZD": 800.67,
                    "SAR": 345.76,
                    "SEK": 123.57,
                    "SGD": 923.71,
                    "THB": 36.11,
                    "USD": 1298
                }
            }
        }
    },
    "published": "2022-07-06T10:36:18Z",
    "updated": "2022-07-06T10:36:18Z"
}
```

## 문의
* gnh1201@gmail.com
