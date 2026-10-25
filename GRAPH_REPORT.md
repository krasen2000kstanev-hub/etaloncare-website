# Graph Report - etaloncare-website  (2026-10-25)

## Corpus Check
- 39 files · ~53,674 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 8 file(s) not represented in the graph (top: (none) 3, .woff2 2, .ics 1)

## Summary
- 278 nodes · 406 edges · 19 communities (15 shown, 4 thin omitted)
- Extraction: 95% EXTRACTED · 5% INFERRED · 0% AMBIGUOUS · INFERRED: 22 edges (avg confidence: 0.94)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `285e443e`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- ref_node_fs
- checkout.mjs
- aws.mjs
- backend.test.mjs
- package.json
- handler.mjs
- SqliteStore
- deploy-lambda-preview.mjs
- booking.js
- booking-120.js
- soon.js
- ETALON CARE — Науката за ноктите
- Graph Report - etaloncare-website  (2026-10-22)
- ETALON CARE — Науката за ноктите
- EtalonCare — project memory

## God Nodes (most connected - your core abstractions)
1. `Service` - 20 edges
2. `SqliteStore` - 18 edges
3. `DynamoStore` - 15 edges
4. `ETALON CARE — Науката за ноктите` - 15 edges
5. `ApiError` - 12 edges
6. `Graph Report - etaloncare-website  (2026-10-22)` - 12 edges
7. `Borica` - 11 edges
8. `Communities (15 total, 5 thin omitted)` - 11 edges
9. `ETALON CARE — Науката за ноктите` - 11 edges
10. `God Nodes (most connected - your core abstractions)` - 10 edges

## Surprising Connections (you probably didn't know these)
- `God Nodes (most connected - your core abstractions)` --references--> `macString()`  [INFERRED]
  GRAPH_REPORT.md → backend/borica.mjs
- `God Nodes (most connected - your core abstractions)` --references--> `Borica`  [INFERRED]
  GRAPH_REPORT.md → backend/borica.mjs
- `God Nodes (most connected - your core abstractions)` --references--> `DynamoStore`  [INFERRED]
  GRAPH_REPORT.md → backend/dynamo.mjs
- `Suggested Questions` --references--> `Service`  [INFERRED]
  GRAPH_REPORT.md → backend/service.mjs
- `God Nodes (most connected - your core abstractions)` --references--> `SqliteStore`  [INFERRED]
  GRAPH_REPORT.md → backend/sqlite.mjs

## Import Cycles
- None detected.

## Communities (19 total, 4 thin omitted)

### Community 0 - "ref_node_fs"
Cohesion: 0.07
Nodes (21): @axe-core/playwright, playwright, files, references, root, saved, production, publicFiles (+13 more)

### Community 1 - "checkout.mjs"
Cohesion: 0.13
Nodes (12): Borica, macString(), RESPONSE, SALE, STATUS, bank, config, handle (+4 more)

### Community 2 - "aws.mjs"
Cohesion: 0.09
Nodes (13): DynamoStore, Suggested Questions, @aws-sdk/client-dynamodb, @aws-sdk/lib-dynamodb, availability, config, created, deployment (+5 more)

### Community 3 - "backend.test.mjs"
Cohesion: 0.13
Nodes (16): responseString(), headers, makeHandler(), ApiError, digest(), safeEqual(), seatPlan(), Service (+8 more)

### Community 4 - "package.json"
Cohesion: 0.12
Nodes (15): dependencies, @aws-sdk/client-dynamodb, @aws-sdk/lib-dynamodb, devDependencies, @axe-core/playwright, playwright, name, private (+7 more)

### Community 5 - "handler.mjs"
Cohesion: 0.16
Nodes (11): apiHandler, config, handler(), payment, store, hash, publicFiles, root (+3 more)

### Community 6 - "SqliteStore"
Cohesion: 0.10
Nodes (8): config, handle, service, SqliteStore, publicFiles, root, server, types

### Community 7 - "deploy-lambda-preview.mjs"
Cohesion: 0.11
Nodes (14): details, full, identity, names, old, outputs, plan, template (+6 more)

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

### Community 16 - "Graph Report - etaloncare-website  (2026-10-22)"
Cohesion: 0.10
Nodes (19): Communities (15 total, 5 thin omitted), Community 0 - "ref_node_fs", Community 11 - "soon.js", Community 1 - "backend.test.mjs", Community 2 - "aws.mjs", Community 3 - "Service", Community 4 - "package.json", Community 5 - "handler.mjs" (+11 more)

### Community 17 - "ETALON CARE — Науката за ноктите"
Cohesion: 0.12
Nodes (15): 1. Visual Theme & Atmosphere, 2. Color Palette & Roles, 3. Typography Rules, 4. Component Stylings, 5. Layout Principles, 6. Depth & Elevation, 7. Animation & Interaction, 8. Do's and Don'ts (+7 more)

### Community 18 - "EtalonCare — project memory"
Cohesion: 0.20
Nodes (9): Current state, Deployment, EtalonCare — project memory, Local verification, Payments and personal data, Repository, Required checks before production, Structure (+1 more)

## Knowledge Gaps
- **4 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Graph Report - etaloncare-website  (2026-10-22)` connect `Graph Report - etaloncare-website  (2026-10-22)` to `aws.mjs`, `backend.test.mjs`?**
  _High betweenness centrality (0.095) - this node is a cross-community bridge._
- **Are the 3 inferred relationships involving `Service` (e.g. with `God Nodes (most connected - your core abstractions)` and `Suggested Questions`) actually correct?**
  _`Service` has 3 INFERRED edges - model-reasoned connections that need verification._
- **Should `ref_node_fs` be split into smaller, more focused modules?**
  _Cohesion score 0.07226890756302522 - nodes in this community are weakly interconnected._
- **Why does `Service` connect `backend.test.mjs` to `checkout.mjs`, `aws.mjs`, `handler.mjs`, `SqliteStore`?**
  _High betweenness centrality (0.078) - this node is a cross-community bridge._
- **Are the 3 inferred relationships involving `SqliteStore` (e.g. with `God Nodes (most connected - your core abstractions)` and `Suggested Questions`) actually correct?**
  _`SqliteStore` has 3 INFERRED edges - model-reasoned connections that need verification._
- **Should `checkout.mjs` be split into smaller, more focused modules?**
  _Cohesion score 0.13450292397660818 - nodes in this community are weakly interconnected._
- **Why does `SqliteStore` connect `SqliteStore` to `checkout.mjs`, `aws.mjs`, `backend.test.mjs`?**
  _High betweenness centrality (0.068) - this node is a cross-community bridge._