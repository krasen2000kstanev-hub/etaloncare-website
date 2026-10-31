# Graph Report - etaloncare-website  (2026-10-31)

## Corpus Check
- 42 files · ~67,879 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 8 file(s) not represented in the graph (top: (none) 3, .woff2 2, .ics 1)

## Summary
- 311 nodes · 469 edges · 26 communities (20 shown, 6 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 15 edges (avg confidence: 0.92)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `193a22e6`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- ref_node_assert
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
- Communities (21 total, 5 thin omitted)
- ETALON CARE — Науката за ноктите
- EtalonCare — project memory
- deploy-preview.mjs
- update-lambda-preview.mjs
- DynamoStore
- dev.mjs
- archive-current.mjs
- publish-preview.mjs
- ref_node_fs

## God Nodes (most connected - your core abstractions)
1. `Service` - 24 edges
2. `DynamoStore` - 20 edges
3. `SqliteStore` - 20 edges
4. `Communities (21 total, 5 thin omitted)` - 17 edges
5. `ETALON CARE — Науката за ноктите` - 15 edges
6. `Borica` - 13 edges
7. `ApiError` - 11 edges
8. `Graph Report - etaloncare-website  (2026-10-26)` - 11 edges
9. `ETALON CARE — Науката за ноктите` - 11 edges
10. `renderEmail()` - 9 edges

## Surprising Connections (you probably didn't know these)
- `God Nodes (most connected - your core abstractions)` --references--> `Borica`  [INFERRED]
  GRAPH_REPORT.md → backend/borica.mjs
- `Surprising Connections (you probably didn't know these)` --references--> `Borica`  [INFERRED]
  GRAPH_REPORT.md → backend/borica.mjs
- `God Nodes (most connected - your core abstractions)` --references--> `DynamoStore`  [INFERRED]
  GRAPH_REPORT.md → backend/dynamo.mjs
- `Suggested Questions` --references--> `Service`  [INFERRED]
  GRAPH_REPORT.md → backend/service.mjs
- `Surprising Connections (you probably didn't know these)` --references--> `Service`  [INFERRED]
  GRAPH_REPORT.md → backend/service.mjs

## Import Cycles
- None detected.

## Communities (26 total, 6 thin omitted)

### Community 0 - "ref_node_assert"
Cohesion: 0.29
Nodes (4): root, englishSchema, ids, schema

### Community 1 - "backend.test.mjs"
Cohesion: 0.08
Nodes (26): Borica, macString(), RESPONSE, responseString(), SALE, STATUS, headers, makeHandler() (+18 more)

### Community 2 - "aws.mjs"
Cohesion: 0.06
Nodes (27): dependencies, @aws-sdk/client-dynamodb, @aws-sdk/lib-dynamodb, nodemailer, devDependencies, @axe-core/playwright, playwright, name (+19 more)

### Community 3 - "Service"
Cohesion: 0.16
Nodes (13): canSplit(), date(), emailsDue(), firstDeadline(), renderEmail(), renderEnglish(), secondDeadline(), ApiError (+5 more)

### Community 4 - "ref_node_child_process"
Cohesion: 0.29
Nodes (5): @axe-core/playwright, playwright, base, report, base

### Community 5 - "handler.mjs"
Cohesion: 0.11
Nodes (15): apiHandler, bank, config, handler(), mailer, payment, store, Smtp (+7 more)

### Community 7 - "deploy-lambda-preview.mjs"
Cohesion: 0.20
Nodes (8): details, full, identity, names, old, outputs, plan, template

### Community 8 - "booking.js"
Cohesion: 0.73
Nodes (5): draw(), message(), refresh(), request(), updateButton()

### Community 9 - "booking-120.js"
Cohesion: 0.73
Nodes (5): draw(), message(), refresh(), request(), updateButton()

### Community 11 - "soon.js"
Cohesion: 0.67
Nodes (3): mount(), tick(), pad()

### Community 15 - "ETALON CARE — Науката за ноктите"
Cohesion: 0.09
Nodes (20): GitHub Pages активен — 05.10.2026, SEO copy update — 07.10.2026, Whole-site motion — 07.10.2026, Анимации L2 — 05.10.2026, Повторна проверка преди смяна на хостинга, Преди реални продажби, Проверка — 03.10.2026, Проверка след обновяването на дизайна (+12 more)

### Community 16 - "Communities (21 total, 5 thin omitted)"
Cohesion: 0.12
Nodes (17): Communities (21 total, 5 thin omitted), Community 0 - "ref_node_fs", Community 11 - "soon.js", Community 15 - "ETALON CARE — Науката за ноктите", Community 16 - "Communities (19 total, 4 thin omitted)", Community 17 - "ETALON CARE — Науката за ноктите", Community 18 - "EtalonCare — project memory", Community 19 - "deploy-preview.mjs" (+9 more)

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
Cohesion: 0.11
Nodes (10): DynamoStore, Community Hubs (Navigation), Corpus Check, Graph Freshness, Graph Report - etaloncare-website  (2026-10-26), Import Cycles, Knowledge Gaps, Suggested Questions (+2 more)

### Community 22 - "dev.mjs"
Cohesion: 0.33
Nodes (4): publicFiles, root, server, types

### Community 23 - "archive-current.mjs"
Cohesion: 0.40
Nodes (4): files, references, root, saved

### Community 24 - "publish-preview.mjs"
Cohesion: 0.40
Nodes (3): cors, deployment, plan

## Knowledge Gaps
- **6 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Graph Report - etaloncare-website  (2026-10-26)` connect `DynamoStore` to `Communities (21 total, 5 thin omitted)`, `Service`?**
  _High betweenness centrality (0.110) - this node is a cross-community bridge._
- **Are the 3 inferred relationships involving `Service` (e.g. with `God Nodes (most connected - your core abstractions)` and `Suggested Questions`) actually correct?**
  _`Service` has 3 INFERRED edges - model-reasoned connections that need verification._
- **Should `backend.test.mjs` be split into smaller, more focused modules?**
  _Cohesion score 0.08205128205128205 - nodes in this community are weakly interconnected._
- **Why does `Service` connect `Service` to `backend.test.mjs`, `DynamoStore`, `aws.mjs`, `handler.mjs`?**
  _High betweenness centrality (0.102) - this node is a cross-community bridge._
- **Are the 3 inferred relationships involving `DynamoStore` (e.g. with `God Nodes (most connected - your core abstractions)` and `Suggested Questions`) actually correct?**
  _`DynamoStore` has 3 INFERRED edges - model-reasoned connections that need verification._
- **Should `aws.mjs` be split into smaller, more focused modules?**
  _Cohesion score 0.06439393939393939 - nodes in this community are weakly interconnected._
- **Why does `DynamoStore` connect `DynamoStore` to `backend.test.mjs`, `aws.mjs`, `Service`, `handler.mjs`?**
  _High betweenness centrality (0.087) - this node is a cross-community bridge._