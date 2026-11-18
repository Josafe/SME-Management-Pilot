# SME Management Pilot — Claude Code instructions

Project facts, commands, the dependency-approval rule (Laravel Boost is not installed; do not install it) and the `php_artisan_password.txt` rule are shared with all agents in `AGENTS.md`, imported here:

@AGENTS.md

# Orchestration policy: delegate to local models via AI-Dev-Harness (optional)

This integration is optional. The Laravel application does not depend on AI-Dev-Harness: it can be set up, developed and tested without it by following `README.md` (setup, `php artisan test` and `./vendor/bin/pint --test` need only PHP and Composer). Only the delegation workflow and the recipes below need the harness, and they work only when AI-Dev-Harness is cloned as a sibling repository of this project (`..\AI-Dev-Harness` from the project root) and a local Ollama server has the models named in *Routing*. If the harness is missing, say so and ask the user whether Claude should do the delegated work directly; never switch silently. The harness is read-only for this project; never modify it from here.

Goal: reduce Claude usage. Claude owns architecture, task decomposition, applying proposals, complex debugging, integration and final review. Local Ollama models handle specification drafting and routine implementation.

## What the harness really provides (verified 2026-11-17)

Invocation: `scripts\run-agent.ps1 -Agent <name> -Task <text> [-Model] [-ContextFile] [-OutputFile]`. It makes one `/api/generate` call to Ollama and returns text. It does not apply files, run tests or use tools. Agents only see the prompt (system prompt + task + context file).

| `-Agent` | Real behavior | Use here? |
|---|---|---|
| `spec` | Returns a specification (Goal, Functional/Non-Functional Requirements, Acceptance Criteria, Edge Cases, Dependencies, Open Questions, Assumptions). Default model deepseek-r1:32b. | Yes: specification work only. Not a general planner, debugger or code reviewer. |
| `developer` | Returns a **proposal**: `SUMMARY`, `FILE: path` + fenced complete file, `CHANGES TO EXISTING FILES`, `ASSUMPTIONS/RISKS/OPEN QUESTIONS`, `SUGGESTED VERIFICATION (NOT EXECUTED)`. Not in `config.json`, so without `-Model` it silently uses the default `qwen2.5-coder:32b`. | Yes: implementation |
| `spec-reviewer`, `spec-reviser` | Built for the harness spec pipeline. | No |

Not usable: `specify.ps1`, `review-spec.ps1`, `revise-spec.ps1` (they write into the harness `specs/` folder). There is **no** local agent for task breakdown, architecture, code review, QA or general problem solving. Do not stretch `spec` or `developer` into those roles and do not invent harness features.

Limits: context window 16384 tokens (keep context files small; builder cap 24000 chars); runner timeout 900 s. Measured speeds: qwen2.5-coder:7b ~80 tok/s (seconds to ~1 min); both 32b models ~2.5 tok/s (a 150-line file takes ~12+ min; a `spec` call ~6 min). A model swap adds ~10–30 s of load.

## Routing

| Work | Owner |
|---|---|
| Specification of a feature/module (requirements, acceptance criteria, edge cases, open questions) | **deepseek-r1:32b** via `spec` agent (mode `requirements` unless told otherwise) |
| Task decomposition | **Claude**, compactly, from the `spec` output. No local planner agent exists. |
| Delegated implementation (default): migrations, models, factories, seeders, form requests, Filament resources/pages, tests; new code of roughly 30+ lines or 2+ files, split into small units | **qwen2.5-coder:7b** via `developer` |
| Escalation only: a 7B task that still fails verification after one retry | **qwen2.5-coder:32b** via `developer` |
| Complex debugging | **Claude** (no local agent for it) |
| Architecture decisions, applying and reviewing proposals, auth/roles/permissions and other security-sensitive code, integration, final review, communication with the user | **Claude** |
| Trivial work: edits under ~30 lines, renames, config, docs, running commands/tests/Pint/artisan, answering questions | **Claude directly. Do not call a local model.** |

## Delegation workflow

1. **Preflight** (Recipe A). If Ollama is down or the model is missing: stop and follow *Failure policy*.
2. **Task card** (20–40 lines): goal, exact files to create or change, signatures, conventions, acceptance criteria including tests, "do not touch anything else". Never ask the agent to implement more than one small unit.
3. **Context** (Recipe B): only the files the agent needs. Never include `.env*`, `php_artisan_password.txt` (see `AGENTS.md`), `vendor/`, lock files or `*.sqlite`.
4. **Run** (Recipe C) with an explicit `-Model` every time (`AI_HARNESS_MODEL` can override config silently). Use `run_in_background: true` for any 32b model (the PowerShell tool limit is 10 min) and wait for the notification instead of polling.
5. **Review**: dry-run the apply step (Recipe D), read the output file, check scope against the task card. Agent claims about tests are worthless; they cannot run anything.
6. **Apply** (Recipe D with `$apply = $true`), then run `php artisan test` and `./vendor/bin/pint --test`.
7. **Correct**: on failure, one retry to the same model with the last ~40 lines of the error. Then escalate 7B → 32B (allowed without asking). If 32B fails, stop and follow *Failure policy*.
8. **Report** one line per delegation: `Delegation: agent=<a> model=<m> run_id=<id> duration=<s> output=<file> result=<applied+tests pass | failed: why>`.

## Failure policy

If Ollama is unreachable, a model is missing, the runner exits non-zero, the output is empty or has no `FILE:` blocks where expected, or the call times out: **stop, tell the user what failed (with the error line) and ask whether to retry, switch model, or let Claude do it.** Never silently replace a local agent with Claude.

## Recipes (PowerShell tool; Windows PowerShell 5.1)

Variables used in all recipes. They are derived from the current location, so run the recipes from inside this project (if the session started elsewhere, `Set-Location` to the project root in the same command). The checks stop the recipe instead of letting it read from or write to another repository, such as the harness:

```powershell
$p = (Get-Location).Path
while ($p -and -not (Test-Path (Join-Path $p 'artisan'))) { $p = Split-Path $p -Parent }
if (-not $p) { throw 'Run the recipes from inside the SME-Management-Pilot project.' }
$h = [IO.Path]::GetFullPath((Join-Path $p '..\AI-Dev-Harness'))
if (-not (Test-Path (Join-Path $h 'scripts\run-agent.ps1'))) { throw "AI-Dev-Harness not found at $h (expected next to this project)." }
$w = Join-Path $p 'storage\logs\ai'
```

`$w` is a scratch folder (git-ignored through `storage/logs/.gitignore`) for manifests, contexts and agent outputs.

**A. Preflight** (no model load):

```powershell
try { (Invoke-RestMethod 'http://localhost:11434/api/tags' -TimeoutSec 5).models.name -join ', ' } catch { "OLLAMA DOWN: $($_.Exception.Message)" }
```

**B. Context** (no LLM; rescan every time, files change):

```powershell
New-Item -ItemType Directory -Force $w | Out-Null
& "$h\scripts\scan-repository.ps1" -RootPath $p -OutputFile "$w\manifest.json" *>&1 | Out-Null
& "$h\scripts\build-context.ps1" -ManifestFile "$w\manifest.json" -Include 'app/Models/User.php','tests/Feature/AdminPanelTest.php' -OutputFile "$w\context.md" *>&1 | Out-Null
```

`-Include` takes project-relative paths that exist in the manifest; the builder rejects traversal and some sensitive patterns (such as `.env`). It does **not** reject `php_artisan_password.txt`: the scanner lists that file in the manifest (name and size only), so never pass it to `-Include`.

**C. Run an agent** (the runner also prints the whole result to stdout; capture it so it does not reach Claude's context, and show only the tail on failure):

```powershell
$task = @'
(task card text here. For the spec agent use exactly this layout:)
SPECIFICATION MODE (AUTHORITATIVE):
requirements

ORIGINAL USER REQUEST:
(request text here)
'@
$env:AI_HARNESS_RUN_ID = 'sme-<short-task-id>'
$log = ''
try {
  $log = & "$h\scripts\run-agent.ps1" -Agent developer -Model 'qwen2.5-coder:7b' -Task $task -ContextFile "$w\context.md" -OutputFile "$w\<task-id>.md" *>&1 | Out-String
  if ($LASTEXITCODE -ne 0) { throw "run-agent exit code $LASTEXITCODE" }
  "OK -> $w\<task-id>.md"
} catch { "AGENT FAILED: $($_.Exception.Message)"; ($log -split "`r?`n" | ? { $_.Trim() } | Select-Object -Last 6) -join "`n" }
$LASTEXITCODE = 0
```

For specification work use `-Agent spec -Model 'deepseek-r1:32b'` (the `-ContextFile` is optional) and run it in the background. For escalation use `-Agent developer -Model 'qwen2.5-coder:32b'`, also in the background.

**D. Apply proposal** (dry-run by default; refuses unsafe paths and existing files; backs up overwritten files). Set `$f`, review the dry-run, then set `$apply = $true` (and `$overwrite = $true` only for existing files you reviewed):

```powershell
$f = "$w\<task-id>.md"; $apply = $false; $overwrite = $false
$t = Get-Content $f -Raw -Encoding UTF8
$rx = [regex]'(?ms)^FILE:[ \t]*(?<p>\S+)[ \t]*\r?\n(?<f>`{3,})(?!`)[^\r\n]*\r?\n(?<b>.*?)\r?\n\k<f>[ \t]*\r?$'
foreach ($m in $rx.Matches($t)) {
  $rel = $m.Groups['p'].Value.Trim('`', '"', "'").Replace('/', '\')
  if (($rel -match '^([A-Za-z]:|\\)') -or ($rel -match '(^|\\)\.\.(\\|$)') -or ($rel -match '^(vendor|\.git|node_modules)(\\|$)') -or ($rel -match '(^|\\)\.env') -or ($rel -match '(^|\\)php_artisan_password\.txt$')) { "REFUSE  $rel"; continue }
  $dest = [IO.Path]::GetFullPath((Join-Path $p $rel))
  if (-not $dest.StartsWith($p + '\')) { "REFUSE  $rel"; continue }
  if ((Test-Path $dest) -and -not $overwrite) { "EXISTS  $rel"; continue }
  if (-not $apply) { "WOULD WRITE  $rel"; continue }
  if (Test-Path $dest) { $bk = Join-Path "$w\backup" $rel; New-Item -ItemType Directory -Force (Split-Path $bk) | Out-Null; Copy-Item $dest $bk -Force }
  New-Item -ItemType Directory -Force (Split-Path $dest) | Out-Null
  [IO.File]::WriteAllText($dest, $m.Groups['b'].Value + "`n", (New-Object Text.UTF8Encoding $false))
  "WROTE   $rel"
}
```

This snippet is an inline helper, not a harness feature. Changes listed only under `CHANGES TO EXISTING FILES` are not applied by it; make them with Edit after review.

## How the user can verify delegation

- Every real model call appends a record to `AI-Dev-Harness\logs\model-calls.jsonl` (agent, model, status, duration, token counts, `run_id`). From the project root:
  `Get-Content '..\AI-Dev-Harness\logs\model-calls.jsonl' -Tail 10 | ConvertFrom-Json | Select timestamp_utc,run_id,agent,model,status,duration_ms,eval_count | Format-Table`
- Agent outputs are saved in `storage\logs\ai\`.
- `ollama ps` shows which model is loaded during a run.
- Claude must print the one-line `Delegation:` report after every delegation. If a task was done directly by Claude, it must say so and why (for example "trivial").
