# gnuboard5-activitypub
[![Discord chat](https://img.shields.io/discord/359930650330923008?logo=discord)](https://discord.gg/dBE65JgcBW)

GNUBOARD5-ActivityPub: ActivityPub (Fediverse) implementation for GNUBOARD5

* https://sir.kr/g5_plugin/10381
* https://codeberg.org/fediverse/delightful-activitypub-development
* https://github.com/gnh1201/topic-activitypub

## 사용 전 설정
  * `apstreams` 게시판 추가
  * `apstreams` 사용자 추가

## 지원기능
- [x] WebFinger
- [x] User
- [x] Inbox
- [x] Outbox
- [x] Followers
- [x] Following
- [x] Liked
- [ ] ~~Shares~~ (inbox/outbox only)
- [x] Geolocation
- [x] File attachment
- [ ] File attachment - Automatically download a remote file to the local server
- [x] Digest/Signature - Outbound
- [ ] ~~Digest/Signature - Inbound~~ (No required)
- [x] w3id.org (e.g., the `publicKey` field of an actor)
- [ ] OAuth 2.0
- [ ] Message Queue integration (e.g., Redis, RebbitMQ, Kafka)

## 부가기능 (선택사항)
- [x] 아바타 (gravatar.com)
- [x] 날씨 (openweathermap.org)
- [x] 환율 (koreaexim.go.kr)
- [x] 국내 Geolocation (Naver Cloud)
- [x] 국외 Geolocation (IP2Location)

## 전문(메시지) 예시

```json
{
    "@context": "https://www.w3.org/ns/activitystreams",
    "type": "Create",
    "id": "http://example.org/bbs/board.php?bo_table=apstreams&wr_id=235",
    "to": ["https://www.w3.org/ns/activitystreams#Public", "http://example.org/?route=activitypub.user&mb_id=admin"],
    "actor": "http://example.org/?route=activitypub.user&mb_id=admin",
    "object": {
        "type": "Note",
        "generator": "GNUBOARD5 ActivityPub Plugin (INSTANCE_ID: 4d6076784cbd864ade7c746690d37051, INSTANCE_VERSION: 0.1.11-dev)",
        "id": "http://example.org/bbs/board.php?bo_table=free&wr_id=1",
        "attributedTo": "http://example.org/?route=activitypub.user&mb_id=admin",
        "content": "안녕하세요 @admin@example.org",
        "icon": "https://www.gravatar.com/avatar/bdbd5eb70305f1eaaa0340687758676a",
        "location": {
            "name": "xxx.xxx.xxx.xxx, 서울특별시 금천구 가산동 (Korea Telecom), Seoul, Seoul-teukbyeolsi, Korea (Republic of), KR, 06030, +09:00",
            "type": "Place",
            "longitude": 126.8917326,
            "latitude": 37.4769094,
            "units": "m",
            "_weather": {
                "dt": 1657163472,
                "sunrise": 1657138663,
                "sunset": 1657191385,
                "temp": 305.42,
                "feels_like": 309.65,
                "pressure": 1005,
                "humidity": 56,
                "dew_point": 295.52,
                "uvi": 8.53,
                "clouds": 100,
                "visibility": 10000,
                "wind_speed": 5.72,
                "wind_deg": 186,
                "wind_gust": 10.14,
                "weather": [{
                    "id": 804,
                    "main": "Clouds",
                    "description": "overcast clouds",
                    "icon": "04d"
                }]
            },
            "_exchange": {
                "KRW": {
                    "AED": 355.94,
                    "AUD": 887.07,
                    "BHD": 3467.72,
                    "BND": 930.73,
                    "CAD": 1003.15,
                    "CHF": 1346.86,
                    "CNH": 194.76,
                    "DKK": 178.9,
                    "EUR": 1331.33,
                    "GBP": 1558.81,
                    "HKD": 166.61,
                    "IDR(100)": 8.72,
                    "JPY(100)": 960.93,
                    "KRW": 0,
                    "KWD": 4253.09,
                    "MYR": 295.49,
                    "NOK": 128.98,
                    "NZD": 804.44,
                    "SAR": 348.27,
                    "SEK": 124.02,
                    "SGD": 930.73,
                    "THB": 36.12,
                    "USD": 1307.4
                }
            }
        }
    },
    "published": "2022-07-07T03:11:12Z",
    "updated": "2022-07-07T03:11:12Z"
}
```

## 참고자료
* https://www.w3.org/TR/activitypub/
* https://www.w3.org/TR/activitystreams-core/
* https://www.w3.org/TR/activitystreams-vocabulary/
* https://github.com/w3c/activitypub/issues/194
* https://docs.joinmastodon.org/spec/webfinger/
* https://organicdesign.nz/ActivityPub_Code
* https://socialhub.activitypub.rocks/t/posting-to-pleroma-inbox/1184
* https://github.com/broidHQ/integrations/tree/master/broid-schemas#readme
* https://github.com/autogestion/pubgate-telegram
* https://docs.joinmastodon.org/spec/security/
* https://chat.openai.com/share/4fda7974-cc0b-439a-b0f2-dc828f8acfef
* https://codeberg.org/mro/activitypub/src/commit/4b1319d5363f4a836f23c784ef780b81bc674013/like.sh#L101
* https://socialhub.activitypub.rocks/t/problems-posting-to-mastodon-inbox/801/10

## 문의
* abuse@catswords.net
* ActivityPub [@gnh1201@catswords.social](https://catswords.social/@gnh1201)
