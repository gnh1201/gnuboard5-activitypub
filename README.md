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
        "generator": "GNUBOARD5 ActivityPub Plugin (INSTANCE_ID: 4d6076784cbd864ade7c746690d37051, INSTANCE_VERSION: 0.1.11-dev)",
        "id": "http://utilhome.dothome.co.kr/bbs/board.php?bo_table=apstreams&wr_id=215",
        "attributedTo": "http://utilhome.dothome.co.kr/?route=activitypub.user&mb_id=admin",
        "content": "날씨 어때요? @admin@utilhome.dothome.co.kr",
        "icon": "https://www.gravatar.com/avatar/bdbd5eb70305f1eaaa0340687758676a",
        "location": {
            "name": "121.88.93.28, 서울특별시 구로구 구로5동 (DLIVE), Seoul, Seoul-teukbyeolsi, Korea (Republic of), KR, 06030, +09:00",
            "type": "Place",
            "longitude": 126.8892945,
            "latitude": 37.5001593,
            "units": "m",
            "_weather": {
                "dt": 1657098169,
                "sunrise": 1657052227,
                "sunset": 1657105004,
                "temp": 305.05,
                "feels_like": 308.32,
                "pressure": 1005,
                "humidity": 54,
                "dew_point": 294.59,
                "uvi": 0.8,
                "clouds": 100,
                "visibility": 10000,
                "wind_speed": 2.72,
                "wind_deg": 126,
                "wind_gust": 3.76,
                "weather": [{
                    "id": 804,
                    "main": "Clouds",
                    "description": "overcast clouds",
                    "icon": "04d"
                }]
            },
            "_exchange": {
                "KRW-AED": {
                    "ttb": "349.84",
                    "tts": "356.91"
                },
                "KRW-AUD": {
                    "ttb": "873.42",
                    "tts": "891.07"
                },
                "KRW-BHD": {
                    "ttb": "3,408.36",
                    "tts": "3,477.21"
                },
                "KRW-BND": {
                    "ttb": "914.47",
                    "tts": "932.94"
                },
                "KRW-CAD": {
                    "ttb": "986.31",
                    "tts": "1,006.24"
                },
                "KRW-CHF": {
                    "ttb": "1,327.01",
                    "tts": "1,353.82"
                },
                "KRW-CNH": {
                    "ttb": "192.21",
                    "tts": "196.1"
                },
                "KRW-DKK": {
                    "ttb": "177.32",
                    "tts": "180.91"
                },
                "KRW-EUR": {
                    "ttb": "1,319.39",
                    "tts": "1,346.04"
                },
                "KRW-GBP": {
                    "ttb": "1,536.43",
                    "tts": "1,567.46"
                },
                "KRW-HKD": {
                    "ttb": "163.76",
                    "tts": "167.07"
                },
                "KRW-IDR(100)": {
                    "ttb": "8.57",
                    "tts": "8.74"
                },
                "KRW-JPY(100)": {
                    "ttb": "947.47",
                    "tts": "966.62"
                },
                "KRW-KRW": {
                    "ttb": "0",
                    "tts": "0"
                },
                "KRW-KWD": {
                    "ttb": "4,183",
                    "tts": "4,267.51"
                },
                "KRW-MYR": {
                    "ttb": "290.76",
                    "tts": "296.63"
                },
                "KRW-NOK": {
                    "ttb": "127.76",
                    "tts": "130.35"
                },
                "KRW-NZD": {
                    "ttb": "792.66",
                    "tts": "808.67"
                },
                "KRW-SAR": {
                    "ttb": "342.3",
                    "tts": "349.21"
                },
                "KRW-SEK": {
                    "ttb": "122.33",
                    "tts": "124.8"
                },
                "KRW-SGD": {
                    "ttb": "914.47",
                    "tts": "932.94"
                },
                "KRW-THB": {
                    "ttb": "35.74",
                    "tts": "36.47"
                },
                "KRW-USD": {
                    "ttb": "1,285.02",
                    "tts": "1,310.98"
                }
            }
        }
    },
    "published": "2022-07-06T09:02:51Z",
    "updated": "2022-07-06T09:02:51Z"
}
```

## 문의
* gnh1201@gmail.com
