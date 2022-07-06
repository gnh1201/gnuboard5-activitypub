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
        "id": "http://example.org/bbs/board.php?bo_table=apstreams&wr_id=218",
        "attributedTo": "http://example.org/?route=activitypub.user&mb_id=admin",
        "content": "날씨 어때요? @admin@example.org",
        "icon": "https://www.gravatar.com/avatar/bdbd5eb70305f1eaaa0340687758676a",
        "location": {
            "name": "xxx.xxx.xxx.xxx, 서울특별시 구로구 구로5동 (DLIVE), Seoul, Seoul-teukbyeolsi, Korea (Republic of), KR, 06030, +09:00",
            "type": "Place",
            "longitude": 126.8892945,
            "latitude": 37.5001593,
            "units": "m",
            "_weather": {
                "dt": 1657099094,
                "sunrise": 1657052227,
                "sunset": 1657105004,
                "temp": 306.04,
                "feels_like": 310.71,
                "pressure": 1005,
                "humidity": 55,
                "dew_point": 295.8,
                "uvi": 0.8,
                "clouds": 75,
                "visibility": 10000,
                "wind_speed": 2.57,
                "wind_deg": 160,
                "weather": [{
                    "id": 803,
                    "main": "Clouds",
                    "description": "broken clouds",
                    "icon": "04d"
                }]
            },
            "_exchange": {
                "KRW-AED": 353.38,
                "KRW-AUD": 882.25,
                "KRW-BHD": 3442.79,
                "KRW-BND": 923.71,
                "KRW-CAD": 996.28,
                "KRW-CHF": 1340.42,
                "KRW-CNH": 194.16,
                "KRW-DKK": 179.12,
                "KRW-EUR": 1332.72,
                "KRW-GBP": 1551.95,
                "KRW-HKD": 165.42,
                "KRW-IDR(100)": 8.66,
                "KRW-JPY(100)": 957.05,
                "KRW-KRW": 0,
                "KRW-KWD": 2135.85,
                "KRW-MYR": 293.7,
                "KRW-NOK": 129.06,
                "KRW-NZD": 800.67,
                "KRW-SAR": 345.76,
                "KRW-SEK": 123.57,
                "KRW-SGD": 923.71,
                "KRW-THB": 36.11,
                "KRW-USD": 1298
            }
        }
    },
    "published": "2022-07-06T09:18:25Z",
    "updated": "2022-07-06T09:18:25Z"
}
```

## 문의
* gnh1201@gmail.com
