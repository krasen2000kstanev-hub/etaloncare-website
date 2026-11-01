# Graph Report - etaloncare-website  (2026-11-01)

## Corpus Check
- 43 files · ~73,711 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 8 file(s) not represented in the graph (top: (none) 3, .woff2 2, .ics 1)

## Summary
- 335 nodes · 519 edges · 25 communities (19 shown, 6 thin omitted)
- Extraction: 96% EXTRACTED · 4% INFERRED · 0% AMBIGUOUS · INFERRED: 20 edges (avg confidence: 0.92)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `628ca055`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- archive-current.mjs
- backend.test.mjs
- aws.mjs
- Service
- ref_node_child_process
- handler.mjs
- SqliteStore
- deploy-lambda-preview.mjs
- booking.js
- booking-120.js
- soon.js
- ETALON CARE — Науката за ноктите
- Communities (27 total, 6 thin omitted)
- ETALON CARE — Науката за ноктите
- EtalonCare — project memory
- deploy-preview.mjs
- update-lambda-preview.mjs
- DynamoStore
- publish-preview.mjs
- ref_node_fs
- admin.js

## God Nodes (most connected - your core abstractions)
1. `Service` - 30 edges
2. `Communities (27 total, 6 thin omitted)` - 22 edges
3. `DynamoStore` - 21 edges
4. `SqliteStore` - 20 edges
5. `ETALON CARE — Науката за ноктите` - 15 edges
6. `ApiError` - 14 edges
7. `Borica` - 13 edges
8. `renderEmail()` - 11 edges
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

## Communities (25 total, 6 thin omitted)

### Community 0 - "archive-current.mjs"
Cohesion: 0.20
Nodes (7): files, references, root, saved, englishSchema, ids, schema

### Community 1 - "backend.test.mjs"
Cohesion: 0.06
Nodes (33): Borica, macString(), RESPONSE, responseString(), SALE, STATUS, bearer(), headers (+25 more)

### Community 2 - "aws.mjs"
Cohesion: 0.06
Nodes (28): seatPlan(), dependencies, @aws-sdk/client-dynamodb, @aws-sdk/lib-dynamodb, nodemailer, devDependencies, @axe-core/playwright, playwright (+20 more)

### Community 3 - "Service"
Cohesion: 0.16
Nodes (11): canSplit(), date(), emailsDue(), firstDeadline(), renderEmail(), renderEnglish(), secondDeadline(), ApiError (+3 more)

### Community 4 - "ref_node_child_process"
Cohesion: 0.24
Nodes (6): @axe-core/playwright, playwright, base, report, root, base

### Community 5 - "handler.mjs"
Cohesion: 0.11
Nodes (15): apiHandler, bank, config, handler(), mailer, payment, store, Smtp (+7 more)

### Community 7 - "deploy-lambda-preview.mjs"
Cohesion: 0.20
Nodes (8): details, full, identity, names, old, outputs, plan, template

### Community 8 - "booking.js"
Cohesion: 0.57
Nodes (5): draw(), message(), refresh(), request(), updateButton()

### Community 9 - "booking-120.js"
Cohesion: 0.57
Nodes (5): draw(), message(), refresh(), request(), updateButton()

### Community 11 - "soon.js"
Cohesion: 0.67
Nodes (3): mount(), tick(), pad()

### Community 15 - "ETALON CARE — Науката за ноктите"
Cohesion: 0.09
Nodes (20): GitHub Pages активен — 05.10.2026, SEO copy update — 07.10.2026, Whole-site motion — 07.10.2026, Анимации L2 — 05.10.2026, Повторна проверка преди смяна на хостинга, Преди реални продажби, Проверка — 03.10.2026, Проверка след обновяването на дизайна (+12 more)

### Community 16 - "Communities (27 total, 6 thin omitted)"
Cohesion: 0.09
Nodes (22): Communities (27 total, 6 thin omitted), Community 0 - "ref_node_assert", Community 11 - "soon.js", Community 15 - "ETALON CARE — Науката за ноктите", Community 16 - "Communities (26 total, 6 thin omitted)", Community 17 - "ETALON CARE — Науката за ноктите", Community 18 - "EtalonCare — project memory", Community 19 - "deploy-preview.mjs" (+14 more)

### Community 17 - "ETALON CARE — Науката за ноктите"
Cohesion: 0.12
Nodes (15): 1. Visual Theme & Atmosphere, 2. Color Palette & Roles, 3. Typography Rules, 4. Component Stylings, 5. Layout Principles, 6. Depth & Elevation, 7. Animation & Interaction, 8. Do's and Don'ts (+7 more)

### Community 18 - "EtalonCare — project memory"
Cohesion: 0.20
Nodes (9): Current state, Deployment, EtalonCare — project memory, Local verification, Payments and personal data, Repository, Required checks before production, Structure (+1 more)

### Community 19 - "deploy-preview.mjs"
Cohesion: 0.25
Nodes (6): details, hash, identity, outputs, plan, template

### Community 20 - "update-lambda-preview.mjs"
Cohesion: 0.33
Nodes (4): config, deployment, plan, update

### Community 21 - "DynamoStore"
Cohesion: 0.10
Nodes (10): DynamoStore, Community Hubs (Navigation), Corpus Check, Graph Freshness, Graph Report - etaloncare-website  (2026-11-01), Import Cycles, Knowledge Gaps, Suggested Questions (+2 more)

### Community 24 - "publish-preview.mjs"
Cohesion: 0.40
Nodes (3): cors, deployment, plan

### Community 26 - "admin.js"
Cohesion: 0.62
Nodes (6): call(), cell(), draw(), load(), logout(), update()

## Knowledge Gaps
- **6 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Graph Report - etaloncare-website  (2026-11-01)` connect `DynamoStore` to `Communities (27 total, 6 thin omitted)`, `backend.test.mjs`?**
  _High betweenness centrality (0.120) - this node is a cross-community bridge._
- **Are the 3 inferred relationships involving `Service` (e.g. with `God Nodes (most connected - your core abstractions)` and `Suggested Questions`) actually correct?**
  _`Service` has 3 INFERRED edges - model-reasoned connections that need verification._
- **Should `backend.test.mjs` be split into smaller, more focused modules?**
  _Cohesion score 0.06292517006802721 - nodes in this community are weakly interconnected._
- **Why does `Service` connect `Service` to `backend.test.mjs`, `aws.mjs`, `DynamoStore`, `handler.mjs`?**
  _High betweenness centrality (0.117) - this node is a cross-community bridge._
- **Are the 3 inferred relationships involving `DynamoStore` (e.g. with `God Nodes (most connected - your core abstractions)` and `Suggested Questions`) actually correct?**
  _`DynamoStore` has 3 INFERRED edges - model-reasoned connections that need verification._
- **Should `aws.mjs` be split into smaller, more focused modules?**
  _Cohesion score 0.06050420168067227 - nodes in this community are weakly interconnected._
- **Why does `Communities (27 total, 6 thin omitted)` connect `Communities (27 total, 6 thin omitted)` to `DynamoStore`?**
  _High betweenness centrality (0.091) - this node is a cross-community bridge._