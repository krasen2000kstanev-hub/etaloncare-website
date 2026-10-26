# Graph Report - etaloncare-website  (2026-10-26)

## Corpus Check
- 39 files · ~59,001 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 8 file(s) not represented in the graph (top: (none) 3, .woff2 2, .ics 1)

## Summary
- 282 nodes · 403 edges · 21 communities (16 shown, 5 thin omitted)
- Extraction: 96% EXTRACTED · 4% INFERRED · 0% AMBIGUOUS · INFERRED: 15 edges (avg confidence: 0.93)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `aa9d5f0f`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- ref_node_fs
- backend.test.mjs
- aws.mjs
- Service
- package.json
- handler.mjs
- SqliteStore
- deploy-lambda-preview.mjs
- booking.js
- booking-120.js
- soon.js
- ETALON CARE — Науката за ноктите
- Communities (19 total, 4 thin omitted)
- ETALON CARE — Науката за ноктите
- EtalonCare — project memory
- deploy-preview.mjs
- update-lambda-preview.mjs

## God Nodes (most connected - your core abstractions)
1. `Service` - 20 edges
2. `SqliteStore` - 18 edges
3. `Communities (19 total, 4 thin omitted)` - 16 edges
4. `DynamoStore` - 15 edges
5. `ETALON CARE — Науката за ноктите` - 15 edges
6. `Borica` - 12 edges
7. `ApiError` - 11 edges
8. `Graph Report - etaloncare-website  (2026-10-25)` - 11 edges
9. `ETALON CARE — Науката за ноктите` - 11 edges
10. `EtalonCare — project memory` - 9 edges

## Surprising Connections (you probably didn't know these)
- `God Nodes (most connected - your core abstractions)` --references--> `Borica`  [INFERRED]
  GRAPH_REPORT.md → backend/borica.mjs
- `God Nodes (most connected - your core abstractions)` --references--> `DynamoStore`  [INFERRED]
  GRAPH_REPORT.md → backend/dynamo.mjs
- `Surprising Connections (you probably didn't know these)` --references--> `DynamoStore`  [INFERRED]
  GRAPH_REPORT.md → backend/dynamo.mjs
- `Suggested Questions` --references--> `Service`  [INFERRED]
  GRAPH_REPORT.md → backend/service.mjs
- `Surprising Connections (you probably didn't know these)` --references--> `Service`  [INFERRED]
  GRAPH_REPORT.md → backend/service.mjs

## Import Cycles
- None detected.

## Communities (21 total, 5 thin omitted)

### Community 0 - "ref_node_fs"
Cohesion: 0.11
Nodes (13): files, references, root, saved, production, publicFiles, publicFiles, root (+5 more)

### Community 1 - "backend.test.mjs"
Cohesion: 0.09
Nodes (19): Borica, macString(), RESPONSE, responseString(), SALE, STATUS, Surprising Connections (you probably didn't know these), bankFields() (+11 more)

### Community 2 - "aws.mjs"
Cohesion: 0.10
Nodes (12): DynamoStore, @aws-sdk/client-dynamodb, @aws-sdk/lib-dynamodb, availability, config, created, deployment, payment (+4 more)

### Community 3 - "Service"
Cohesion: 0.16
Nodes (11): headers, makeHandler(), config, handle, service, ApiError, digest(), safeEqual() (+3 more)

### Community 4 - "package.json"
Cohesion: 0.07
Nodes (23): dependencies, @aws-sdk/client-dynamodb, @aws-sdk/lib-dynamodb, devDependencies, @axe-core/playwright, playwright, name, private (+15 more)

### Community 5 - "handler.mjs"
Cohesion: 0.16
Nodes (11): apiHandler, config, handler(), payment, store, hash, publicFiles, root (+3 more)

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

### Community 16 - "Communities (19 total, 4 thin omitted)"
Cohesion: 0.08
Nodes (24): Communities (19 total, 4 thin omitted), Community 0 - "ref_node_fs", Community 11 - "soon.js", Community 15 - "ETALON CARE — Науката за ноктите", Community 16 - "Graph Report - etaloncare-website  (2026-10-22)", Community 17 - "ETALON CARE — Науката за ноктите", Community 18 - "EtalonCare — project memory", Community 1 - "checkout.mjs" (+16 more)

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

## Knowledge Gaps
- **5 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Graph Report - etaloncare-website  (2026-10-25)` connect `Communities (19 total, 4 thin omitted)` to `backend.test.mjs`, `Service`?**
  _High betweenness centrality (0.112) - this node is a cross-community bridge._
- **Are the 3 inferred relationships involving `Service` (e.g. with `God Nodes (most connected - your core abstractions)` and `Suggested Questions`) actually correct?**
  _`Service` has 3 INFERRED edges - model-reasoned connections that need verification._
- **Should `ref_node_fs` be split into smaller, more focused modules?**
  _Cohesion score 0.10822510822510822 - nodes in this community are weakly interconnected._
- **Why does `Service` connect `Service` to `backend.test.mjs`, `aws.mjs`, `handler.mjs`, `SqliteStore`, `Communities (19 total, 4 thin omitted)`?**
  _High betweenness centrality (0.090) - this node is a cross-community bridge._
- **Are the 3 inferred relationships involving `SqliteStore` (e.g. with `God Nodes (most connected - your core abstractions)` and `Suggested Questions`) actually correct?**
  _`SqliteStore` has 3 INFERRED edges - model-reasoned connections that need verification._
- **Should `backend.test.mjs` be split into smaller, more focused modules?**
  _Cohesion score 0.09475806451612903 - nodes in this community are weakly interconnected._
- **Are the 2 inferred relationships involving `DynamoStore` (e.g. with `God Nodes (most connected - your core abstractions)` and `Surprising Connections (you probably didn't know these)`) actually correct?**
  _`DynamoStore` has 2 INFERRED edges - model-reasoned connections that need verification._