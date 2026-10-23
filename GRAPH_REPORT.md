# Graph Report - etaloncare-website  (2026-10-22)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 207 nodes · 324 edges · 15 communities (10 shown, 5 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 3 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from repository snapshot: `f9d85643af5ae015a585ae62818cd86f90d80b2d`
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

## God Nodes (most connected - your core abstractions)
1. `Service` - 17 edges
2. `SqliteStore` - 15 edges
3. `DynamoStore` - 13 edges
4. `Borica` - 10 edges
5. `ApiError` - 10 edges
6. `digest()` - 7 edges
7. `responseString()` - 6 edges
8. `makeHandler()` - 6 edges
9. `scripts` - 6 edges
10. `macString()` - 5 edges

## Surprising Connections (you probably didn't know these)
- `setup()` --calls--> `Service`  [EXTRACTED]
  tests/backend.test.mjs → backend/service.mjs
- `bankFields()` --calls--> `responseString()`  [EXTRACTED]
  tests/backend.test.mjs → backend/borica.mjs
- `setup()` --calls--> `SqliteStore`  [EXTRACTED]
  tests/backend.test.mjs → backend/sqlite.mjs
- `makeHandler()` --calls--> `ApiError`  [EXTRACTED]
  backend/http.mjs → backend/service.mjs

## Import Cycles
- None detected.

## Communities (15 total, 5 thin omitted)

### Community 0 - "ref_node_fs"
Cohesion: 0.05
Nodes (30): playwright, files, references, root, saved, production, publicFiles, details (+22 more)

### Community 1 - "backend.test.mjs"
Cohesion: 0.10
Nodes (18): Borica, macString(), RESPONSE, responseString(), SALE, STATUS, bankFields(), baseConfig (+10 more)

### Community 2 - "aws.mjs"
Cohesion: 0.10
Nodes (12): DynamoStore, @aws-sdk/client-dynamodb, @aws-sdk/lib-dynamodb, availability, config, created, deployment, payment (+4 more)

### Community 3 - "Service"
Cohesion: 0.16
Nodes (10): headers, makeHandler(), config, handle, service, ApiError, digest(), safeEqual() (+2 more)

### Community 4 - "package.json"
Cohesion: 0.12
Nodes (16): dependencies, @aws-sdk/client-dynamodb, @aws-sdk/lib-dynamodb, devDependencies, @axe-core/playwright, playwright, name, private (+8 more)

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

## Knowledge Gaps
- **5 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Service` connect `Service` to `backend.test.mjs`, `aws.mjs`, `handler.mjs`, `SqliteStore`?**
  _High betweenness centrality (0.092) - this node is a cross-community bridge._
- **Should `ref_node_fs` be split into smaller, more focused modules?**
  _Cohesion score 0.051418439716312055 - nodes in this community are weakly interconnected._
- **Why does `SqliteStore` connect `SqliteStore` to `backend.test.mjs`, `Service`?**
  _High betweenness centrality (0.085) - this node is a cross-community bridge._
- **Should `backend.test.mjs` be split into smaller, more focused modules?**
  _Cohesion score 0.0967741935483871 - nodes in this community are weakly interconnected._
- **Why does `DynamoStore` connect `aws.mjs` to `handler.mjs`?**
  _High betweenness centrality (0.081) - this node is a cross-community bridge._
- **Should `aws.mjs` be split into smaller, more focused modules?**
  _Cohesion score 0.09538461538461539 - nodes in this community are weakly interconnected._
- **Should `package.json` be split into smaller, more focused modules?**
  _Cohesion score 0.11764705882352941 - nodes in this community are weakly interconnected._

## Synchronization — 23.10.2026
- The graph artifacts `graph.html` and `graph.json` are tracked in the repository.
- `graph.json` metadata is synchronized with the current `main` snapshot.
- Recent updates are primarily public copy and documentation changes; the code relationships represented by the map remain unchanged.
- For future structural code changes, regenerate the graph and update this report’s snapshot reference.
