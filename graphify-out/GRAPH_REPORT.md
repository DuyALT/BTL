# Graph Report - BTL-GitHub  (2026-11-11)

## Corpus Check
- 50 files · ~29,936 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 7 file(s) not represented in the graph (top: (none) 4, .css 3)

## Summary
- 134 nodes · 137 edges · 46 communities (5 shown, 41 thin omitted)
- Extraction: 85% EXTRACTED · 15% INFERRED · 0% AMBIGUOUS · INFERRED: 21 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `a2f5c041`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- luu_diem
- auth.php
- functions.php
- backup.php
- schema.sql
- error-handler.php
- si_so
- add_column

## God Nodes (most connected - your core abstractions)
1. `luu_diem()` - 16 edges
2. ``users`` - 5 edges
3. ``lop_hoc_phan`` - 5 edges
4. `current_user()` - 5 edges
5. `tinh_lai_theo_mon()` - 5 edges
6. `doc_sao_luu()` - 5 edges
7. ``sinh_vien`` - 4 edges
8. ``dang_ky`` - 4 edges
9. ``diem`` - 4 edges
10. `require_login()` - 4 edges

## Surprising Connections (you probably didn't know these)
- `{closure#10}()` --calls--> `luu_diem()`  [INFERRED]
  database/test_features.php → includes/grade-service.php
- `{closure#2}()` --calls--> `luu_diem()`  [INFERRED]
  database/test_features.php → includes/grade-service.php
- `{closure#3}()` --calls--> `luu_diem()`  [INFERRED]
  database/test_features.php → includes/grade-service.php
- `{closure#4}()` --calls--> `luu_diem()`  [INFERRED]
  database/test_features.php → includes/grade-service.php
- `{closure#5}()` --calls--> `luu_diem()`  [INFERRED]
  database/test_features.php → includes/grade-service.php

## Import Cycles
- None detected.

## Communities (46 total, 41 thin omitted)

### Community 0 - "luu_diem"
Cohesion: 0.20
Nodes (12): check(), {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+4 more)

### Community 1 - "auth.php"
Cohesion: 0.16
Nodes (14): co_quyen_lhp(), csrf_field(), csrf_token(), current_gv_id(), current_sv_id(), current_user(), forbidden(), h() (+6 more)

### Community 2 - "functions.php"
Cohesion: 0.22
Nodes (8): diem_thuong_xuyen(), fd(), hien_thi_diem(), quy_doi(), tinh_lai_theo_mon(), tinh_tong_ket(), xet_ket_qua(), trung_binh()

### Community 3 - "backup.php"
Cohesion: 0.24
Nodes (6): {closure#11}(), {closure#12}(), doc_sao_luu(), khoi_phuc(), tao_sao_luu(), parse_diem()

### Community 4 - "schema.sql"
Cohesion: 0.36
Nodes (11): `dang_ky`, `diem`, `giang_vien`, `hoc_ky`, `lich_su_diem`, `lop_hanh_chinh`, `lop_hoc_phan`, `mon_hoc` (+3 more)

## Knowledge Gaps
- **41 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `options()` connect `auth.php` to `functions.php`?**
  _High betweenness centrality (0.080) - this node is a cross-community bridge._
- **Are the 14 inferred relationships involving `luu_diem()` (e.g. with `{closure#10}()` and `{closure#2}()`) actually correct?**
  _`luu_diem()` has 14 INFERRED edges - model-reasoned connections that need verification._
- **Why does `luu_diem()` connect `luu_diem` to `functions.php`, `backup.php`?**
  _High betweenness centrality (0.068) - this node is a cross-community bridge._