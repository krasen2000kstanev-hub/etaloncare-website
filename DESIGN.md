# ETALON CARE — Науката за ноктите

Статус: одобрено от клиента на 03.10.2026. Източници: документът „за лендинг с Дъг Шун.docx“ и предоставената брошура в D:\Etalon. Избран е отделен сайт на основния etaloncare.com, с картово плащане през UniCredit и бъдеща потвърдена схема на местата.

## 1. Visual Theme & Atmosphere

Изчистен редакционен дизайн за професионална конференция: научна достоверност, простор, ясна йерархия и дискретни геометрични детайли. Тъмносин първи екран с голямо бяло заглавие; светли информационни секции; тюркоазени акценти. Цветовете са визуално съобразени с брошурата, а не представени като официални бранд кодове.

Предложено ниво L1: деликатно появяване и hover ефекти с CSS. Без анимационни библиотеки. Изискването за изчистен сайт има предимство пред ефектни 3D сцени и декоративни анимации.

## 2. Color Palette & Roles

```css
:root {
  --navy: #0c153c; --navy-rgb: 12,21,60;
  --blue: #1c2d70; --blue-rgb: 28,45,112;
  --teal: #116b80; --teal-rgb: 17,107,128;
  --accent: #73d4dc; --accent-rgb: 115,212,220;
  --accent-hover: #a1e6eb; --accent-hover-rgb: 161,230,235;
  --bg: #ffffff; --bg-rgb: 255,255,255;
  --surface: #f3f6f8; --surface-rgb: 243,246,248;
  --text: #14213c; --text-rgb: 20,33,60;
  --muted: #526079; --muted-rgb: 82,96,121;
  --border: #dbe3ea; --border-rgb: 219,227,234;
  --success: #176b51; --success-rgb: 23,107,81;
  --error: #b3233c; --error-rgb: 179,35,60;
}
```

Всички цветове в реализацията използват променливите. Тюркоазът е фон на основните бутони с тъмносин текст, а не светъл текст върху бяло. Проверка за WCAG AA контраст при QA.

## 3. Typography Rules

Manrope с кирилица, при възможност локално доставен; системен fallback при липса на шрифт.

```css
@import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap');
body { font-family: 'Manrope', system-ui, sans-serif; }
```

| Елемент | Размер desktop / mobile | Тежест | Междуредие |
|---|---|---|---|
| H1 | 80 / 44 px, fluid | 800 | 1.08 |
| H2 | 48 / 32 px, fluid | 700 | 1.15 |
| H3 | 24 / 22 px | 700 | 1.3 |
| Основен текст | 18 / 16 px | 400 | 1.65 |
| Етикет | 13 px | 700 | 1.5 |
| Цена | 56 / 44 px | 800 | 1.1 |

Без градиенти и сенки в текста. Без декоративни, script и monospace шрифтове за съдържание. По един H1; логична H2/H3 йерархия.

## 4. Component Stylings

```css
.button { display: inline-flex; align-items: center; justify-content: center; min-height: 48px; padding: 12px 24px; border: 1px solid var(--accent); border-radius: 8px; background: var(--accent); color: var(--navy); font-weight: 700; text-decoration: none; transition: background .18s, transform .18s; }
.button:hover { background: var(--accent-hover); }
.button:active { transform: translateY(1px); }
:is(a, button, summary, input):focus-visible { outline: 3px solid var(--teal); outline-offset: 5px; }
.hero :is(a,button):focus-visible { outline-color: var(--accent); }
.button:disabled, .button[aria-disabled="true"] { opacity: .55; cursor: not-allowed; transform: none; }
.card { padding: 32px; border: 1px solid var(--border); border-radius: 16px; background: var(--bg); }
.card.featured { border: 2px solid var(--teal); }
.card a:hover { text-decoration: underline; text-underline-offset: 5px; }
.nav { display: flex; align-items: center; gap: 24px; min-height: 80px; }
.nav a { min-height: 44px; display: inline-flex; align-items: center; color: var(--text); text-decoration: none; }
.nav a:hover, .nav a:active { color: var(--teal); text-decoration: underline; text-underline-offset: 6px; }
.text-link { color: var(--teal); text-underline-offset: 4px; }
.text-link:hover { text-decoration-thickness: 2px; }
.text-link:active { color: var(--navy); }
.badge { display: inline-flex; padding: 6px 12px; border: 1px solid var(--border); border-radius: 100px; font-size: 13px; }
summary { min-height: 48px; padding: 16px 0; cursor: pointer; font-weight: 700; }
summary:hover { color: var(--teal); }
summary:active { opacity: .8; }
```

Картите и етикетите са статични, без подвеждащ hover. FAQ използва native details/summary. Преди 01.11.2026 CTA води до ясна информация за отварянето на регистрацията. Не показва фиктивна успешна резервация. След датата работеща покупка се активира само когато има свързан реален процес.

## 5. Layout Principles

Контейнер 1200 px, странични отстояния 24 px desktop / 20 px mobile. Текстови колони до 680 px. Скала на отстоянията: 8, 16, 24, 32, 48, 64, 96 px.

Ред на секциите:
1. Навигация с лого ETALON CARE, теми, лектор, участие, FAQ.
2. Hero: „Науката за ноктите“, Дъг Шун, дата, София, място, превод и CTA; снимка от предоставените материали, ако качеството позволява.
3. Ключови факти: 2 дни, 100 участници, симултанен превод.
4. „Когато знаеш защо, работиш по-уверено“ — кратък смислов преход.
5. Теми: научни факти, анатомия, химия, безопасност, проблеми и практика; една по-голяма водеща карта и компактни останали теми.
6. Дъг Шун: биография, 35+ години опит, книги и научна работа, по предоставения документ.
7. За кого е събитието: маникюристи, педикюристи, преподаватели и собственици на салони.
8. Два пакета: Стандарт 600 € / Премиум 700 €, точно описани включени услуги, 20 премиум места, две вноски до края на януари 2027 г.
9. Място и принцип на избор на седалка; без измислена схема на залата.
10. FAQ: език, регистрация, пакети, плащане, избор на места.
11. Финален CTA и footer с организатор, потвърдени контакти и приложими условия.

```css
.container { width: min(1200px, calc(100% - 48px)); margin-inline: auto; }
.section { padding-block: 96px; }
.two-column { display: grid; grid-template-columns: 1.1fr .9fr; gap: 64px; }
.packages { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px; }
```

## 6. Depth & Elevation

Основните секции са плоски. Карти с тънък контур; премиум пакетът с тюркоазен контур. Една дискретна сянка за визуалния акцент:

```css
.hero-media { box-shadow: 0 24px 64px rgba(var(--navy-rgb), .16); }
```

Без стъклени панели и blur ефекти. Геометричният мотив от брошурата се използва пестеливо като декоративен SVG с aria-hidden.

## 7. Animation & Interaction

Предложение L1; нуждае се от потвърждение. CSS only, без зависимости. Еднократно плавно появяване на hero, native scroll и hover, без блокиране на съдържанието при изключен JavaScript.

```css
@keyframes appear { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
.hero-copy { animation: appear .6s ease-out both; }
html { scroll-behavior: smooth; }
[id] { scroll-margin-top: 96px; }
@media (prefers-reduced-motion: reduce) {
  html { scroll-behavior: auto; }
  *, *::before, *::after { animation: none !important; transition: none !important; }
}
```

Дискретен полезен детайл: „Добави в календара“ с локален .ics файл, без външен акаунт. Не се измислят начални часове; събитието е обозначено по дни до потвърждение на програмата.

## 8. Do's and Don'ts

Прави: използвай реалните материали; пази четимостта на кирилицата; показвай цените ясно; поддържай клавиатурна навигация; запази съдържанието достъпно без JS; оптимизирай изображенията.

Не прави:
- Не добавяй непоискани framework, CMS, база данни или библиотеки за анимация.
- Не използвай scroll hijacking, курсор ефекти или 3D.
- Не измисляй отзиви, спонсори, програма по часове или оставащи места.
- Не представяй локално избрана седалка като реална резервация.
- Не добавяй работещ на вид формуляр без реален получател.
- Не обещавай позиция в Google.
- Не публикувай изходния документ или тежките оригинали по подразбиране.
- Не въвеждай платени облачни услуги при изискване за нулев бюджет.
- Не добавяй аналитични cookies и тракери без потребност.

SEO: български lang, смислен title и description, Open Graph, canonical към потвърдения публичен URL, sitemap.xml и robots.txt. Event JSON-LD с фактическите дати, място, организатор и цени; без фиктивна наличност на билети или рейтинг. Важното съдържание присъства в HTML.

QA след реализация: desktop и mobile 360/390/768/1440 px, overflow, четимост, контраст, клавиатура, меню, FAQ, всички CTA/връзки, календар, липсващи изображения, конзолни грешки, metadata и JSON-LD. Поправки преди публикуване; резултатите се записват в QA.md.

## 9. Responsive Behavior

Desktop > 1024 px: две колони в hero и биографията. Tablet 601–1024 px: по-малки отстояния и едноколонен hero. Mobile ≤ 600 px: една колона, двата пакета един под друг, бутони на ширината на контейнера. Минимална зона за докосване 44×44 px. Навигацията се опростява с достъпно меню, ако не се побира.

```css
@media (max-width: 1024px) { .two-column { grid-template-columns: 1fr; gap: 32px; } .section { padding-block: 64px; } }
@media (max-width: 600px) {
  .container { width: calc(100% - 40px); }
  .packages { grid-template-columns: 1fr; }
  .section { padding-block: 48px; }
  .card { padding: 24px; }
  .button { width: 100%; box-sizing: border-box; }
}
```

## Публикуване и бюджет — решения извън визуалния дизайн

Предложение: статични HTML/CSS и минимален JS, код в GitHub, доставка чрез AWS CloudFront Free flat-rate plan и частен S3 origin. Не EC2, RDS или Kubernetes. GitHub е хранилище, AWS обслужва сайта.

Към 03.10.2026 CloudFront Free е обявен за $0/месец, с 1 млн. заявки, 100 GB трансфер и включени 5 GB S3 storage credits. Това не означава, че всички операции или други AWS ресурси са безплатни. Преди създаване се проверяват планът, допустимостта на акаунта и конкретните S3/публикационни разходи. Не се избира pay-as-you-go по подразбиране.

Източник: https://aws.amazon.com/cloudfront/pricing/

Съществуващ домейн може да се използва при достъп до DNS. Нов домейн и картови платежни такси не са част от безплатния хостинг. Избрано е картово плащане през UniCredit; таксите и конкретният банков интерфейс се потвърждават от банката.

Потвърдени са дизайнът, etaloncare.com и GitHub/AWS акаунтите. Остават разрешен достъп до DNS, банковите документи/тестов терминал, официална схема на залата, контакти, правила за вноските и условия за участниците. Не се искат пароли или тайни ключове в чата.

## Одобрени подобрения — 03.10.2026

Черно-белият портрет и корицата са от предоставените оригинали viber_image в D:/Etalon. Портретът запълва арката с object-fit: cover, без текст от брошурата. Декоративният монограм е заменен с книгата. На мобилен екран са съкратени отстоянията, повторенията и декоративните блокове. Темите са последвани от компактни карти с двата дни; часовете и разпределението остават непубликувани до потвърждение. Общите услуги на пакетите се показват веднъж, а картите подчертават избора на място и премиум допълненията. Основният CTA е „Разгледай пакетите“; при включена регистрация, одобрена схема и настъпила начална дата става „Избери място“.

## Одобрени L2 анимации — 05.10.2026

Секциите се появяват веднъж за 450 ms с движение 14 px, темите с разлика 70 ms. Съдържанието остава видимо преди наблюдението и без JavaScript. Портретът се появява меко, орбитите правят едно бавно движение за 4 секунди и спират. Пакетите се повдигат 4 px при hover само на desktop. Местата сменят цвета за 150 ms, а променена цена се обновява за 180 ms. На мобилен екран се оставят кратко появяване и обратна връзка при избор. prefers-reduced-motion изключва всички ефекти, включително при промяна на настройката по време на разглеждане.

## Разширение за целия сайт — 07.10.2026

Началната страница добавя header transition, hero title/label entrance и бавен фон. Регистрацията добавя stagger на редовете и полетата, а FAQ — плавно отваряне и завъртане на индикатора. Payment, privacy и 404 използват същия finite page entrance чрез общия CSS. Няма външна animation библиотека; reduced-motion остава изключен навсякъде.

## SEO copy update — 07.10.2026

Основният title и description вече използват естествени търсения като „конференция за нокти“, „Дъг Шун“, „София“, „химия на продуктите“ и „безопасна професионална работа“. H1 и секциите са разширени с конкретни отговори за структурата на нокътя, продуктите, аудиторията и мястото на събитието. Добавени са три FAQ въпроса с полезни отговори. Индексирането остава изключено до официалния launch.
