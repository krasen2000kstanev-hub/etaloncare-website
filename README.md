# ETALON CARE — Науката за ноктите

Конференция с Дъг Шун, 21–22 март 2027 г., София. Самостоятелен статичен сайт с отделна AWS система за резервации и подготвен UniCredit/BORICA адаптер.

## Стартиране и проверки

Node.js 24 за локалната SQLite проверка; Lambda използва Node.js 22 без SQLite.

```sh
npm ci
npm run dev
npm test
npx playwright install chromium
npm run test:ui
```

На Windows browser QA използва инсталиран Chrome; на Linux използва Chromium на Playwright. `TEST_BROWSER=chromium` избира Chromium изрично. `npm run dev:api` стартира затворената локална API система на порт 4174. Локалният preview е на http://127.0.0.1:4173. Публикуван AWS преглед: https://qbtajhfhmvagn5lxglooo53woe0etuic.lambda-url.eu-central-1.on.aws/ — резултатите и ограниченията са в [QA.md](QA.md).

Статичният сайт няма framework или runtime зависимости. AWS SDK се използва само от Lambda. Playwright и axe са само за QA. Шрифтовете и предоставените изображения се доставят локално.

## Какво работи и какво още не е активирано

- Информационен сайт, пакети, FAQ, календар и мобилна навигация.
- Избор на едно място; общата наличност се чете от AWS.
- Атомарно записване на място, поръчка и банков номер с DynamoDB transaction.
- Повторно изпращане на същата заявка не създава втора поръчка.
- Подготовка на BORICA MAC_GENERAL sale request, RSA-SHA256 подписи, проверка на подпис и точна сума/валута/терминал/nonce на отговора.
- Проверка на пропуснат банков отговор чрез TRTYPE=90; състояние pending не освобождава място само по локален часовник.
- Реални заявки са блокирани до потвърдени схема, условия, терминал и дата 01.11.2026.

Примерната схема е 10×10 с 20 обозначени премиум позиции. **Това не е схемата на хотела.** Организаторът обещава премиум на първи ред; примерните два премиум реда не трябва да се активират. Подредбата се сменя в `backend/event.json`; frontend показва схемата от API при достъпна система.

Картовият адаптер е реализиран по публичното ръководство BORICA P-OM-41, v7.0 / 17.04.2026, MAC_GENERAL с RFU. Не е одобрен от банката и не е преминал нейния sandbox с реалния търговски терминал. Банката трябва да потвърди този интерфейс и схема за конкретния терминал; при различен протокол адаптерът се променя преди активиране.

## Реални плащания — необходима подготовка

1. UniCredit предоставя test и production TID/MID и потвърждава MAC_GENERAL/версията. На терминала се регистрира точният HTTPS BACKREF към `/api/payment/callback` на Lambda URL.
2. RSA-2048 private key и passphrase се настройват като криптирани Lambda environment variables. Не се записват в Git, браузъра, HTML, документацията или чата. Отделни ключове и терминали за test/production.
3. BORICA public key/certificate се получава по доверения официален канал, проверява се с банката и се планира ротация.
4. Секретните настройки са `BORICA_TERMINAL`, `BORICA_MERCHANT`, `BORICA_NAME`, `BORICA_PRIVATE_KEY`, `BORICA_PASSPHRASE`, `BORICA_PUBLIC_KEY`, `BORICA_BACKREF`, `BORICA_MODE`. `SITE_URL` е https://etaloncare.com. За размер над лимита на Lambda environment variables е нужен отделно оценен secret storage.
5. Потвърждават се юридическите данни, privacy notice, правила за отказ/възстановяване, условията за двете вноски и контактите. `termsApproved` и `seatPlanApproved` стават true само след потвърждение.
6. Sandbox QA с реалния test terminal: успешно/отказано плащане, затворен браузър, загубено връщане, повторен callback, неподписан callback, неправилна сума, едновременно избиране, статус проверка и отказ/връщане през банковия портал.
7. Активира се периодичният `ReconcileRule` на 15 минути. Неуреден резултат след 24 часа изисква проверка в банковия портал; мястото не се освобождава на сляпо. Наблюдава се CloudWatch логът за броя unresolved, без лични данни.
8. `registrationEnabled` се включва в backend/event.json и config.js, `preview=false` в публичния сайт; датата се проверява и от сървъра. Не се включва автоматично само по календар.

Само пълно плащане е реализирано в тази версия. Две вноски са описани информационно; checkout за депозит и остатък ще се добави след потвърдени суми, срокове и банков процес. Няма автоматичен refund; възстановяванията се правят през банковия Merchant Portal и се съгласуват с резервацията. Няма изпращане на имейли; статусът е достъпен в същия браузър чрез случаен секретен token в sessionStorage. Ако имейл билети са необходими, трябва да се одобри доставчик и бюджетът му.

## AWS структура и бюджет

```
GitHub → static assets → private S3 → CloudFront → посетители
                              Lambda URL → Lambda → DynamoDB transactions
                              Lambda ↔ UniCredit/BORICA
EventBridge → Lambda reconciliation (disabled until payment setup)
```

Шаблонът `infra/template.json` създава частен S3 origin с OAC, CloudFront HTTPS, заглавки за сигурност, Lambda, таблица с provisioned capacity и ограничени IAM права. Достъпът за публичния Function URL е само за HTTP; вътрешното reconciliation не е публичен HTTP endpoint. Седалките нямат TTL: банковият резултат определя освобождаването. TTL е само за rate-limit записи.

При първото публикуване CloudFront отказа създаване с „Your account must be verified before you can add new CloudFront resources“. Необходимо е потвърждение чрез AWS Support от собственика. Временният преглед се обслужва през Lambda Function URL; `scripts/deploy-lambda-preview.mjs` използва вече създадената празна таблица от опита за CloudFront, а статичните файлове са част от Lambda пакета. Това е временен preview, не замества постоянния CloudFront план. Предишният публичен сайт е архивиран в output/previous-public-site; това не включва PHP, база, частни настройки или поръчки и не замества архив от SuperHosting.

Текущият одобрен AWS акаунт 674948790853 е FREE, с $140 кредити при проверката на 03.10.2026, и изтича **28.02.2027**, преди събитието. `deploy-preview.mjs` и `publish-preview.mjs` отказват действие, ако акаунтът вече не е ACTIVE FREE. Те не сменят account plan. Preview използва кредити, не постоянен $0 CloudFront flat-rate план.

CloudFront flat-rate Free е $0/месец, но е недостъпен за FREE account plan. Lambda, DynamoDB, S3 заявки и други AWS услуги имат отделни лимити/цени. Няма обещание за безплатна production система при произволен трафик. Не се добавят EC2, RDS, NAT, платени secrets или платен DNS zone.

За deployment:

```sh
python scripts/package.py
node scripts/deploy-preview.mjs
node scripts/publish-preview.mjs
```

На този компютър Python може да се извика чрез bundled runtime пътя. `deployment.local.json` съдържа resource IDs и не е в Git. Шаблонът генерира CSP hash за JSON-LD. Изходният SHA placeholder е заменен преди CloudFormation.

DNS остава при SuperHosting. Когато има разрешен достъп и одобрен постоянен AWS бюджет/план: издаване на безплатен ACM сертификат в us-east-1 за etaloncare.com и www.etaloncare.com, DNS validation CNAME, добавяне на aliases към CloudFront и промяна само на web DNS записите. MX/TXT за пощата се пазят. Запазва се архив на текущия сайт за книгата преди смяна. `node scripts/build-site.mjs --production` подготвя indexable production файловете; utility/checkout страниците остават noindex.

## Прехвърляне към друг собственик

GitHub repository може да се прехвърли или клонира. AWS ресурсите се създават наново в новия акаунт чрез шаблона; export/import на DynamoDB се прави извън Git и след проверка на защитата на личните данни. Нов акаунт изисква нови IAM роли, сертификат, bucket policy, Function URL, CORS/CSP и банков BACKREF. Банковите ключове се издават/настройват от собственика, а не се пренасят в публичен архив. DNS се превключва последен. Наличните поръчки се пренасят с кратка пауза на нови покупки, за да няма две независими наличности.

## Официални източници

- https://3dsgate-dev.borica.bg/
- https://3dsgate-dev.borica.bg/P-OM-41_BORICA_eCommerce_CGI_interface_v%207.0_EN.pdf
- https://www.unicreditbulbank.bg/bg/malak-biznes/bankirane/terminali/virtualen-pos-terminal/
- https://aws.amazon.com/cloudfront/pricing/
- https://docs.aws.amazon.com/PricingPlanManager/latest/UserGuide/plans.html
- https://aws.amazon.com/lambda/pricing/
- https://aws.amazon.com/dynamodb/pricing/

Няма гаранция за позиция в Google. Техническата SEO основа е проверена локално; Search Console, sitemap submission, реална индексация и Core Web Vitals се проверяват след публикуването на домейна.

## Публикуване на отделен статичен хостинг

Изходната директория е output/site; build command: `node scripts/build-site.mjs --production`. Настройката ETALON_API_URL трябва да е HTTPS адресът на AWS API, без тайни данни. deployment.local.json е локална алтернатива, която не се пренася чрез Git. При смяна на origin се проверяват Lambda CORS, SITE_URL, банковият BACKREF и CSP на публичния хостинг. Достъпът до API и тестовете от новия origin се проверяват преди DNS превключването.

GitHub Pages има ограничение за сайтове с основна цел търговски сделки; няма активиран Pages deployment. Новият хостинг предстои да бъде избран. Преместването на сайта не отменя домейна или пощата при стария доставчик. Текущият MX сочи към etaloncare.com и трябва да бъде отделен от web адреса преди превключване.

## Структура като Sofia Summit Center — 05.10.2026

Публичните index.html, booking.html, payment.html, privacy.html, styles.css, JavaScript и assets/ са в корена. backend/ съдържа отделната AWS система, infra/ — инфраструктурата, tests/ — проверките. public-files.json е списъкът за публикуване; output/site съдържа само разрешените публични файлове. backend и локалните настройки не влизат в публичния артефакт. Новото основно хранилище е etaloncare-website с main. Старото etalon-conference е запазено.

GitHub Pages workflow е подготвен, но публикуването се включва само с repository variable ETALON_PAGES_ENABLED=true, след като Pages е достъпен за този частен repository. Стандартният преглед остава noindex и със затворена регистрация. Домейнът и пощата не са променени. Не е променяна видимостта на repository.

GitHub Pages активирането на 05.10.2026 е отказано с HTTP 422: текущият план не поддържа Pages за това частно хранилище. main е основният branch, кодът и workflow са качени. Нужно е изрично разрешение преди промяна на видимостта към public. Репозиторито и DNS остават непроменени по видимост/адрес.

## GitHub Pages активен — 05.10.2026

С изрично разрешение от собственика etaloncare-website е public. Pages е активиран чрез GitHub Actions от main; ETALON_PAGES_ENABLED=true. Публикуването и автоматичният QA са успешни. Публичен адрес: https://krasen2000kstanev-hub.github.io/etaloncare-website/ . AWS CORS допуска този origin; реална браузърна заявка от Pages получава 100 места и потвърждава затворената регистрация. Публикуваният артефакт съдържа само сайта и assets. Банковите ключове и локалните настройки остават извън Git.

etaloncare.com и пощата още не са прехвърлени. Pages прегледът е noindex; реалните продажби остават блокирани до потвърждение на схемата, условията и UniCredit sandbox.
