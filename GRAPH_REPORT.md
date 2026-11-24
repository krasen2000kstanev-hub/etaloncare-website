# Graph Report - etaloncare-website  (2026-11-24)

## Corpus Check
- 43 files · ~55,495 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 9 file(s) not represented in the graph (top: (none) 4, .woff2 2, .ics 1)

## Summary
- 373 nodes · 584 edges · 28 communities (22 shown, 6 thin omitted)
- Extraction: 96% EXTRACTED · 4% INFERRED · 0% AMBIGUOUS · INFERRED: 21 edges (avg confidence: 0.92)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `f9c16544`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- aws.mjs
- backend.test.mjs
- pages
- Service
- DynamoStore
- handler.mjs
- ETALON CARE — Науката за ноктите
- Communities (21 total, 5 thin omitted)
- SqliteStore
- ETALON CARE — Науката за ноктите
- service.mjs
- EtalonCare — project memory
- deploy-lambda-preview.mjs
- ref_node_child_process
- deploy-preview.mjs
- admin.js
- booking.js
- booking-120.js
- dev.mjs
- update-lambda-preview.mjs
- seo.mjs
- archive-current.mjs
- ref_node_fs
- soon.js

## God Nodes (most connected - your core abstractions)
1. `Service` - 31 edges
2. `DynamoStore` - 22 edges
3. `SqliteStore` - 21 edges
4. `Communities (21 total, 5 thin omitted)` - 17 edges
5. `ApiError` - 15 edges
6. `ETALON CARE — Науката за ноктите` - 15 edges
7. `Borica` - 13 edges
8. `renderEmail()` - 12 edges
9. `Graph Report - etaloncare-website  (2026-11-01)` - 11 edges
10. `ETALON CARE — Науката за ноктите` - 11 edges

## Surprising Connections (you probably didn't know these)
- `Surprising Connections (you probably didn't know these)` --references--> `Borica`  [INFERRED]
  GRAPH_REPORT.md → backend/borica.mjs
- `God Nodes (most connected - your core abstractions)` --references--> `DynamoStore`  [INFERRED]
  GRAPH_REPORT.md → backend/dynamo.mjs
- `God Nodes (most connected - your core abstractions)` --references--> `renderEmail()`  [INFERRED]
  GRAPH_REPORT.md → backend/emails.mjs
- `Surprising Connections (you probably didn't know these)` --references--> `renderEmail()`  [INFERRED]
  GRAPH_REPORT.md → backend/emails.mjs
- `God Nodes (most connected - your core abstractions)` --references--> `ApiError`  [INFERRED]
  GRAPH_REPORT.md → backend/service.mjs

## Import Cycles
- None detected.

## Communities (28 total, 6 thin omitted)

### Community 0 - "aws.mjs"
Cohesion: 0.06
Nodes (33): seatPlan(), dependencies, @aws-sdk/client-dynamodb@3.1146.0, @aws-sdk/lib-dynamodb@3.1146.0, nodemailer@10.3.1, devDependencies, @axe-core/playwright@^4.13.0, playwright@^1.63.0 (+25 more)

### Community 1 - "backend.test.mjs"
Cohesion: 0.08
Nodes (29): Borica, macString(), RESPONSE, responseString(), SALE, STATUS, bearer(), headers (+21 more)

### Community 2 - "pages"
Cohesion: 0.07
Nodes (33): ETALON_API_URL, name, url, jobs, pages, qa, name, on (+25 more)

### Community 3 - "Service"
Cohesion: 0.17
Nodes (5): holdUntil, ApiError, digest(), safeEqual(), Service

### Community 4 - "DynamoStore"
Cohesion: 0.10
Nodes (10): DynamoStore, Community Hubs (Navigation), Corpus Check, Graph Freshness, Graph Report - etaloncare-website  (2026-11-01), Import Cycles, Knowledge Gaps, Suggested Questions (+2 more)

### Community 5 - "handler.mjs"
Cohesion: 0.11
Nodes (15): apiHandler, bank, config, handler(), mailer, payment, store, Smtp (+7 more)

### Community 6 - "ETALON CARE — Науката за ноктите"
Cohesion: 0.10
Nodes (19): GitHub Pages активен — 05.10.2026, SEO copy update — 07.10.2026, Whole-site motion — 07.10.2026, Анимации L2 — 05.10.2026, Повторна проверка преди смяна на хостинга, Преди реални продажби, Проверка — 03.10.2026, Проверка след обновяването на дизайна (+11 more)

### Community 7 - "Communities (21 total, 5 thin omitted)"
Cohesion: 0.12
Nodes (17): Communities (21 total, 5 thin omitted), Community 0 - "ref_node_fs", Community 11 - "soon.js", Community 15 - "ETALON CARE — Науката за ноктите", Community 16 - "Communities (25 total, 6 thin omitted)", Community 17 - "ETALON CARE — Науката за ноктите", Community 18 - "EtalonCare — project memory", Community 1 - "backend.test.mjs" (+9 more)

### Community 9 - "ETALON CARE — Науката за ноктите"
Cohesion: 0.12
Nodes (15): 1. Visual Theme & Atmosphere, 2. Color Palette & Roles, 3. Typography Rules, 4. Component Stylings, 5. Layout Principles, 6. Depth & Elevation, 7. Animation & Interaction, 8. Do's and Don'ts (+7 more)

### Community 10 - "service.mjs"
Cohesion: 0.43
Nodes (12): canSplit(), date(), emailsDue(), esc(), firstDeadline(), holdDays(), reminderDay(), renderConfirmation() (+4 more)

### Community 11 - "EtalonCare — project memory"
Cohesion: 0.20
Nodes (9): Current state, Deployment, EtalonCare — project memory, Local verification, Payments and personal data, Repository, Required checks before production, Structure (+1 more)

### Community 12 - "deploy-lambda-preview.mjs"
Cohesion: 0.20
Nodes (8): details, full, identity, names, old, outputs, plan, template

### Community 13 - "ref_node_child_process"
Cohesion: 0.25
Nodes (4): cors, deployment, plan, root

### Community 14 - "deploy-preview.mjs"
Cohesion: 0.25
Nodes (6): details, hash, identity, outputs, plan, template

### Community 15 - "admin.js"
Cohesion: 0.62
Nodes (6): call(), cell(), draw(), load(), logout(), update()

### Community 16 - "booking.js"
Cohesion: 0.57
Nodes (5): draw(), message(), refresh(), request(), updateButton()

### Community 17 - "booking-120.js"
Cohesion: 0.57
Nodes (5): draw(), message(), refresh(), request(), updateButton()

### Community 18 - "dev.mjs"
Cohesion: 0.33
Nodes (4): publicFiles, root, server, types

### Community 19 - "update-lambda-preview.mjs"
Cohesion: 0.33
Nodes (4): config, deployment, plan, update

### Community 20 - "seo.mjs"
Cohesion: 0.40
Nodes (3): englishSchema, ids, schema

### Community 21 - "archive-current.mjs"
Cohesion: 0.40
Nodes (4): files, references, root, saved

### Community 24 - "soon.js"
Cohesion: 0.67
Nodes (3): mount(), tick(), pad()

## Knowledge Gaps
- **6 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Service` connect `Service` to `aws.mjs`, `backend.test.mjs`, `DynamoStore`, `handler.mjs`, `service.mjs`?**
  _High betweenness centrality (0.093) - this node is a cross-community bridge._
- **Are the 2 inferred relationships involving `Service` (e.g. with `God Nodes (most connected - your core abstractions)` and `Suggested Questions`) actually correct?**
  _`Service` has 2 INFERRED edges - model-reasoned connections that need verification._
- **Should `aws.mjs` be split into smaller, more focused modules?**
  _Cohesion score 0.05647840531561462 - nodes in this community are weakly interconnected._
- **Why does `Graph Report - etaloncare-website  (2026-11-01)` connect `DynamoStore` to `backend.test.mjs`, `Communities (21 total, 5 thin omitted)`?**
  _High betweenness centrality (0.090) - this node is a cross-community bridge._
- **Are the 3 inferred relationships involving `DynamoStore` (e.g. with `God Nodes (most connected - your core abstractions)` and `Suggested Questions`) actually correct?**
  _`DynamoStore` has 3 INFERRED edges - model-reasoned connections that need verification._
- **Should `backend.test.mjs` be split into smaller, more focused modules?**
  _Cohesion score 0.0753045404208195 - nodes in this community are weakly interconnected._
- **Why does `Актуален статус — 26.10.2026` connect `service.mjs` to `ETALON CARE — Науката за ноктите`?**
  _High betweenness centrality (0.078) - this node is a cross-community bridge._