# SCHEDULING_ALGORITHMS_COMPARISON_REPORT

## 1) Scope
This report explains how the two scheduling algorithms work in this codebase and compares their runtime and output quality using the current backend configuration and dataset.

Current config snapshot:
- Backtracking `max_seconds`: `300.0`
- Genetic Algorithm `max_seconds`: `10.0`
- Source: `config/optitime.php`

## 2) How Backtracking Works (Current Implementation)
Main file: `app/Scheduling/BacktrackingScheduler.php`

Flow:
1. Builds initial assignment state from fixed sessions (`solve()`).
2. Precomputes legal candidate placements for each non-fixed event (`prepareCandidatePools()`):
- Filters by hard constraints before recursion (room status/capacity/lab/capacity-threshold/instructor availability).
- Pre-sorts candidates by soft ordering score + stable tie-breakers.
3. Runs feasibility prechecks (`precheckImpossible()`) for obvious impossible states.
4. Uses DFS search (`search()`) with:
- Most-constrained-first event selection.
- Incremental in-memory conflict maps for room/instructor/section/day overlaps.
- Forward checking on impacted unscheduled events.
- Failed-state caching.
- Symmetry pruning.
- Hard limits (time/backtracks/recursive steps).
5. If feasible complete assignment is found, applies local soft improvement (`localSoftImprove()`) and returns.

Important behavior:
- Stops as soon as a valid full schedule is found (then local-improves).
- Does not keep searching globally for a potentially better alternative once a valid solution is accepted.

## 3) How Genetic Algorithm Works (Current Implementation)
Main file: `app/Scheduling/GeneticScheduler.php`

Flow:
1. Builds candidate pool per event (`buildCandidatePool()`) using hard filters.
2. Creates initial population (`randomIndividual()` + `repair()`).
3. For each generation (`solve()`):
- Evaluates each individual with hard constraints + soft penalty.
- Keeps elites.
- Produces children using tournament selection + crossover + focused mutation + repair.
4. Stops when one of these occurs:
- time budget reached,
- max generations reached,
- convergence early-stop triggers.
5. Returns best individual found in budget.

Important behavior:
- Budget-driven optimization process (searches many alternatives).
- Quality depends on time budget and GA parameters.
- Can return better soft quality than a first-feasible approach in some datasets, but not guaranteed.

## 4) Measured Comparison on Current Dataset
Benchmark method:
- Same semester
- Same seed (`42`)
- Two runs each
- Called through `ScheduleGenerateService::generate()`
- Date: `2026-05-13`

### Backtracking results
- Run 1: `9.157s`, hard violations `0`, soft penalty `471.77254385765764`
- Run 2: `9.229s`, hard violations `0`, soft penalty `471.77254385765764`
- Average time: `9.193s`
- Average soft penalty: `471.772544`

### Genetic Algorithm results
- Run 1: `10.472s`, hard violations `0`, soft penalty `1306.7725438576592`, generations `15`, termination `max_seconds_exceeded`
- Run 2: `10.874s`, hard violations `0`, soft penalty `1341.0582581433737`, generations `13`, termination `max_seconds_exceeded`
- Average time: `10.673s`
- Average soft penalty: `1323.915401`

## 5) Who Takes More Time?
On this dataset and current config:
- GA takes more time (`~10.67s`) than Backtracking (`~9.19s`).

Reason:
- GA is budget-driven and continues exploring population/generations until budget condition; current runs stop by `max_seconds_exceeded`.
- Backtracking finds a valid solution relatively quickly due pruning and exits earlier.

## 6) Who Gives Better / More Accurate Results?
### Accuracy (hard constraints correctness)
Both are accurate when `success=true`:
- Final response is validated with hard constraints in `ScheduleGenerateService::buildGenerationResponse()`.
- In benchmark runs, both had `hard_violations = 0`.

### Quality (soft constraints)
On this dataset:
- Backtracking produced better soft quality (lower penalty): `471.77` vs GA `1323.92` average.

Interpretation:
- In this specific case, Backtracking currently gives both faster and better-quality results.
- This is dataset/config dependent, not a universal theorem.

## 7) Practical Comparison Summary
Backtracking:
- Pros: Fast feasibility in current data, deterministic behavior, strong pruning, low hard-failure risk.
- Cons: Not a broad global optimizer once first feasible schedule is accepted.

Genetic Algorithm:
- Pros: Broader search strategy, useful for exploring alternatives under complex landscapes.
- Cons: More expensive per run, quality is sensitive to parameter tuning and time budget.

## 8) Recommendation for Current System State
Given current data and settings:
- Use Backtracking as default for weekly generation when response time and current quality are priority.
- Keep GA as optional mode for experimentation/tuning scenarios.
- If GA is expected to outperform quality-wise, it still needs dataset-specific tuning (population/generations/mutation/repair strategy and termination policy).

## 9) Comparison Table
| Criterion | Backtracking | Genetic Algorithm (GA) | Better in Current Dataset |
|---|---|---|---|
| Main approach | Deterministic DFS + pruning + forward checking | Population-based evolutionary search | Depends on problem |
| Goal style | Find feasible schedule quickly, then local improve | Explore many alternatives within time budget | Depends on tuning/budget |
| Average runtime (measured) | `9.193s` | `10.673s` | Backtracking |
| Hard-constraint validity (measured) | `0` hard violations | `0` hard violations | Tie |
| Soft penalty (measured, lower is better) | `471.772544` | `1323.915401` | Backtracking |
| Stopping behavior | Stops after feasible + local improve | Budget/generation/convergence based | Backtracking is faster here |
| Stability on current data | High and consistent in measured runs | Sensitive to time budget and parameters | Backtracking |
| Best use case | Default weekly generation now | Experimental/tuning mode | Backtracking (current state) |
