# EtalonCare — project memory
## Repository
- Repository: https://github.com/krasen2000kstanev-hub/etaloncare-website
- Primary branch: main
- The repository is public.
- The project is a static Etalon Care conference website with a separate AWS reservation/payment backend.
## Structure
- Public frontend files are in the repository root: index.html, booking.html, payment.html, privacy.html, app.js, booking.js, payment.js, config.js and styles.css.
- assets/ contains local fonts and images.
- backend/ contains the AWS/Lambda reservation system.
- infra/ contains infrastructure templates.
- scripts/ contains build and deployment utilities.
- tests/ contains automated checks.
- output/site is the generated public artifact and must contain only approved public files.
## Current state
- The public site and preview workflow are prepared.
- Registration remains closed until the event scheme, terms, dates and banking setup are explicitly approved.
- The example seating scheme is not the hotel seating plan and must not be activated as production data.
- The frontend reads availability from AWS when the API is available.
- Reservation writes use an atomic DynamoDB transaction and repeated requests must remain idempotent.
## Payments and personal data
- Only full payment is implemented in this version; installments are informational only.
- The BORICA/UniCredit adapter is not approved for live production and must pass the bank sandbox with the real test terminal first.
- Refunds are handled through the Merchant Portal; there is no automatic refund flow.
- Email tickets are not implemented.
- Never commit private keys, passphrases, merchant credentials, deployment.local.json, personal data or orders to Git.
- Banking secrets belong only in encrypted Lambda environment variables or an approved secret store.
## Deployment
- The temporary preview uses a Lambda Function URL.
- A permanent CloudFront/S3 deployment still requires AWS account verification and an approved production plan.
- DNS remains with the current provider; preserve MX/TXT mail records when changing web DNS.
- etaloncare.com and mail have not been moved as part of the preview.
- The GitHub Pages preview is noindex and is not the production payment host.
## Required checks before production
1. Confirm the final seating plan, prices, terms, refund rules and contacts.
2. Obtain UniCredit/BORICA test and production identifiers and confirm the exact protocol and BACKREF.
3. Configure separate test and production keys outside Git.
4. Run sandbox QA for success, failure, lost return, repeated callback, invalid signature, wrong amount, concurrent selection and reconciliation.
5. Enable registration only after server-side validation and all approvals are complete.
6. Build the production artifact and verify CORS, CSP, SITE_URL, DNS and payment callbacks.
## Local verification
npm ci
npm run dev
npm test
npm run test:ui
npm run dev:api
This file is a project memory summary. README.md and QA.md remain the authoritative detailed project documentation.


## Synchronization — 26.10.2026
- Live public site: https://etaloncare.com/ is served by GitHub Pages from `main` (pages job in qa.yml); DNS is in Cloudflare; mail and cPanel stay at SuperHosting.
- Book site: https://book.etaloncare.com/ is a separate PHP app on AWS EC2 behind Cloudflare (Full strict rule for that host only).
- Venue: Интер Експо Център, зала Витоша, бул. „Цариградско шосе“ 147, София (home, FAQ, booking, privacy, calendar, Event schema, assets/social.jpg).
- Share image: assets/social.jpg is a poster with the venue and a QR code to https://etaloncare.com/; bump its `?v=` in index.html when it changes, because social networks cache it.
- Hero facts include ЕЗИК (simultaneous Bulgarian translation, lecture in English); FAQ mentions the translation again.
- Speaker card uses assets/doug-schoon-chair.webp?v=2 (seated photo, cut out, 4x Real-ESRGAN + Lanczos blend); on desktop the card height follows the text column and the photo is contained at the bottom.
- Topic labels: 01 / НАУЧНИТЕ ФАКТИ, 02 / НОКЪТЯТ, 03 / ХИМИЯТА, 04 / БЕЗОПАСНОСТТА, 05 / ПРОБЛЕМИТЕ (15px). Card 04 text is about factors affecting safety and quality of professional work.
- Hero title accent line „НАУКАТА ЗАД НОКТИТЕ“ is 0.7em. Venue paragraph mentions simultaneous translation and all nail professionals from beginners to educators.
- CI QA job timeout is 20 minutes; a Playwright install timeout cancels the run and skips the Pages deploy.
- Home page has a back-to-top link (.to-top) shown after 600px of scroll.
- Bump the `?v=` query on styles.css/app.js in the HTML pages whenever they change; browsers cache them for 4 hours.
- Payment mode: backend/event.json `paymentMode: "request"` records name, email, phone and seat (status `requested`, group `requested` in DynamoDB) with no card data or bank; unpaid requests expire after `requestDays` (14) in `reconcile`, which frees the seat. To keep a seat after a transfer, the organizer must change `status` and `group` from `requested` (e.g. to `deposit`) before day 14; the rule ReconcileRule must be enabled for expiry to run. Instalments and reminder emails are not built yet. `"card"` restores the BORICA flow and shows the hidden card fields.
- Registration remains disabled; the booking screen shows the venue plan (Floorplan 30.10.2026): rows 1-9, 14 seats each, aisles after seats 4 and 10, 126 in total; rows 1-2 are the 28 premium seats (assumed, the plan does not mark them). Seat ids are row + two-digit number, e.g. 105.
- Graph artifacts are tracked in `graph.html`, `graph.json`, and `GRAPH_REPORT.md`; do not commit secrets, personal data, orders, or local deployment settings.
