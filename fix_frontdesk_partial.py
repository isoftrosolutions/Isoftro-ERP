#!/usr/bin/env python3
"""
Front Desk SPA Partial-Check Fix Script
========================================
Audits and patches all 22 broken Front Desk PHP view files so they correctly
handle the ?partial=true SPA fetch parameter.

Root cause:
  PHP view files do NOT check for ?partial, so they always return a full
  HTML page (with <html>, header, sidebar, </html>) even when JavaScript
  fetches them as SPA content fragments.  This injects duplicate layout
  chrome into the page and causes the infinite-loading screen bug.

Fix applied per file (4 surgical patches):
  Block A — Wrap `$pageTitle`, `require_once header_1.php`, and
             `require_once sidebar.php` inside  if (!isset($_GET['partial']))
  Block B — Wrap `renderFrontDeskHeader()` and `renderFrontDeskSidebar(...)`
             calls inside  if (!isset($_GET['partial']))
  Block C — Remove the outer <main class="main" ...> wrapper tag
  Block D — Wrap the footer calls (`renderSuperAdminCSS()`, </body>, </html>)
             inside  if (!isset($_GET['partial']))

Files already correct (skipped):
  profile.php, password.php, notifications.php,
  batches.php, courses.php, batch-status.php

Usage:
  python fix_frontdesk_partial.py            # dry-run (preview only)
  python fix_frontdesk_partial.py --apply    # apply all patches + create .bak
  python fix_frontdesk_partial.py --restore  # restore all .bak files
"""

import os
import re
import sys
import shutil
import argparse
from pathlib import Path

# ── Configuration ─────────────────────────────────────────────────────────────

VIEWS_DIR = Path(__file__).parent / "resources" / "views" / "front-desk"

# Files that already have the partial check — skip them
ALREADY_FIXED = {
    "profile.php", "password.php", "notifications.php",
    "batches.php", "courses.php", "batch-status.php",
    # Non-view files — never touch
    "header.php", "sidebar.php", "index.php",
}

# All files that need fixing (22 total)
TARGET_FILES = [
    # P0 — Critical
    "students.php",
    "admission-form.php",
    "inquiries.php",
    "inquiry-add.php",
    "inquiry-followup.php",
    "fee-collect.php",
    # P1 — Important
    "inquiry-report.php",
    "documents.php",
    "id-cards.php",
    "sms-send.php",
    "fee-daily.php",
    "fee-outstanding.php",
    "fee-receipts.php",
    # P2 — Secondary
    "email-send.php",
    "book-issue.php",
    "book-return.php",
    "book-overdue.php",
    "attendance-mark.php",
    "attendance-report.php",
    "report-daily.php",
    "report-revenue.php",
    "report-enrollment.php",
    "report-fees.php",
]

# ── Patch Logic ───────────────────────────────────────────────────────────────

def patch_file(content: str, filename: str) -> tuple[str, list[str]]:
    """
    Apply all four patch blocks to the file content.
    Returns (patched_content, list_of_applied_patch_names).
    """
    applied = []

    # ── Block A: Wrap header requires ─────────────────────────────────────────
    # Matches:
    #   $pageTitle = '...';
    #   require_once VIEWS_PATH . '/layouts/header_1.php';
    #   require_once __DIR__ . '/sidebar.php';
    #
    # Note: some files don't have $pageTitle before the requires, so each
    # sub-pattern is individually optional.

    block_a_pattern = re.compile(
        r"([ \t]*\$pageTitle\s*=\s*'[^']*';\s*\r?\n)?"          # optional $pageTitle
        r"([ \t]*require_once\s+VIEWS_PATH\s*\.\s*['\"]"
        r"/layouts/header_1\.php['\"];\s*\r?\n)"                 # header_1.php
        r"([ \t]*require_once\s+__DIR__\s*\.\s*['\"]"
        r"/sidebar\.php['\"];\s*\r?\n)",                          # sidebar.php
    )

    def replace_block_a(m: re.Match) -> str:
        page_title = m.group(1) or ""
        header_req = m.group(2)
        sidebar_req = m.group(3)
        inner = page_title + header_req + sidebar_req
        # Indent content by 4 spaces inside the if block
        indented = "\n".join("    " + line if line.strip() else line
                             for line in inner.splitlines())
        return f"if (!isset($_GET['partial'])) {{\n{indented}\n}}\n"

    new_content, n = block_a_pattern.subn(replace_block_a, content)
    if n:
        content = new_content
        applied.append("Block A (header requires)")

    # ── Block B: Wrap renderFrontDeskHeader / renderFrontDeskSidebar ──────────
    # Matches PHP short-echo tags like:
    #   <?php renderFrontDeskHeader(); ?>
    #   <?php renderFrontDeskSidebar('students'); ?>
    # OR plain PHP calls without echo tags.

    block_b_pattern = re.compile(
        r"(<\?php\s+renderFrontDeskHeader\(\);\s*\?>\s*\r?\n)"
        r"(<\?php\s+renderFrontDeskSidebar\([^)]*\);\s*\?>\s*\r?\n)",
    )

    def replace_block_b(m: re.Match) -> str:
        # Extract just the function call from `<?php renderX(...); ?>`
        header_call  = re.sub(r'^<\?php\s*|\s*\?>$', '', m.group(1).strip())
        sidebar_call = re.sub(r'^<\?php\s*|\s*\?>$', '', m.group(2).strip())
        return (
            "<?php\n"
            "if (!isset($_GET['partial'])) {\n"
            f"    {header_call}\n"
            f"    {sidebar_call}\n"
            "}\n"
            "?>\n"
        )

    new_content, n = block_b_pattern.subn(replace_block_b, content)
    if n:
        content = new_content
        applied.append("Block B (render calls)")

    # ── Block C: Remove <main class="main" ...> opening tag ──────────────────
    # The wrapper is always `<main class="main"` with an optional id attribute.
    # We simply strip the opening tag; the content inside stays intact.

    block_c_pattern = re.compile(
        r'<main\s+class="main"[^>]*>\s*\r?\n',
    )

    new_content, n = block_c_pattern.subn("", content)
    if n:
        content = new_content
        applied.append("Block C (<main> tag removed)")

    # ── Block D: Wrap footer (renderSuperAdminCSS + </body></html>) ───────────
    # Matches trailing pattern that may look like:
    #
    #   <?php
    #   renderSuperAdminCSS();
    #   echo '<script src="...frontdesk.js"></script>';
    #   ?>
    #   </body>
    #   </html>
    #
    # OR a single-line variant:
    #   <?php renderSuperAdminCSS(); echo '...'; ?>
    #   </body></html>

    # First try the multi-line PHP block variant
    block_d_multi = re.compile(
        r"(<\?php\s*\r?\n)"                                   # <?php
        r"((?:[ \t]*\S[^\n]*\r?\n)+?)"                        # body lines
        r"(\?>\s*\r?\n)"                                       # ?>
        r"([ \t]*</body>\s*\r?\n)"                             # </body>
        r"([ \t]*</html>\s*\r?\n?)",                           # </html>
        re.MULTILINE,
    )

    def replace_block_d_multi(m: re.Match) -> str:
        php_body  = m.group(2)
        body_tag  = m.group(4).strip()
        html_tag  = m.group(5).strip()

        # Only wrap if it contains renderSuperAdminCSS
        if "renderSuperAdminCSS" not in m.group(0):
            return m.group(0)

        indented_body = "\n".join("    " + line if line.strip() else line
                                  for line in php_body.rstrip().splitlines())
        return (
            "<?php\n"
            "if (!isset($_GET['partial'])) {\n"
            f"{indented_body}\n"
            f"    echo '{body_tag}{html_tag}';\n"
            "}\n"
            "?>\n"
        )

    new_content, n = block_d_multi.subn(replace_block_d_multi, content)
    if n:
        content = new_content
        applied.append("Block D (footer wrap)")
    else:
        # Fallback: single-line `<?php renderSuperAdminCSS(); ... ?>` + tags
        block_d_single = re.compile(
            r"(<\?php\s+renderSuperAdminCSS\(\);[^?]*\?>\s*\r?\n)"
            r"([ \t]*</body>\s*\r?\n)"
            r"([ \t]*</html>\s*\r?\n?)",
        )

        def replace_block_d_single(m: re.Match) -> str:
            php_line = m.group(1).rstrip("\r\n")
            return (
                "<?php\n"
                "if (!isset($_GET['partial'])) {\n"
                f"    {php_line.lstrip('<?php').rstrip('?>').strip()}\n"
                "    echo '</body></html>';\n"
                "}\n"
                "?>\n"
            )

        new_content, n = block_d_single.subn(replace_block_d_single, content)
        if n:
            content = new_content
            applied.append("Block D (footer wrap — single line)")

    # ── Also remove stray </main> that matched the removed <main> ─────────────
    # Only remove if Block C was applied (i.e. we removed a <main> tag)
    if "Block C (<main> tag removed)" in applied:
        # Remove the closing </main> that sits just before </body> or before
        # the PHP footer block we just wrapped.
        content = re.sub(r"\n?[ \t]*</main>\s*\r?\n", "\n", content)

    return content, applied


# ── File Helpers ──────────────────────────────────────────────────────────────

def backup_file(path: Path):
    bak = path.with_suffix(".php.bak")
    shutil.copy2(path, bak)
    return bak


def restore_file(path: Path) -> bool:
    bak = path.with_suffix(".php.bak")
    if bak.exists():
        shutil.copy2(bak, path)
        bak.unlink()
        return True
    return False


# ── Main ──────────────────────────────────────────────────────────────────────

def run(apply: bool, restore: bool):
    if not VIEWS_DIR.exists():
        print(f"[ERROR] Views directory not found: {VIEWS_DIR}")
        sys.exit(1)

    if restore:
        print("── RESTORE MODE ────────────────────────────")
        for fname in TARGET_FILES:
            path = VIEWS_DIR / fname
            if restore_file(path):
                print(f"  ✅ Restored  {fname}")
            else:
                print(f"  ⚠️  No backup  {fname}")
        print("Done.")
        return

    mode_label = "APPLY MODE" if apply else "DRY-RUN MODE (no files changed)"
    print(f"── {mode_label} ──────────────────────────────")
    print(f"   Views dir : {VIEWS_DIR}\n")

    success_count = 0
    skip_count    = 0
    error_count   = 0

    for fname in TARGET_FILES:
        path = VIEWS_DIR / fname
        if not path.exists():
            print(f"  ⚠️  MISSING   {fname}")
            skip_count += 1
            continue

        if fname in ALREADY_FIXED:
            print(f"  ✅ SKIP      {fname}  (already correct)")
            skip_count += 1
            continue

        # Read
        original = path.read_text(encoding="utf-8", errors="replace")

        # Check if already patched
        if "isset($_GET['partial'])" in original:
            print(f"  ✅ SKIP      {fname}  (partial check already present)")
            skip_count += 1
            continue

        # Patch
        try:
            patched, applied = patch_file(original, fname)
        except Exception as exc:
            print(f"  ❌ ERROR     {fname}  — {exc}")
            error_count += 1
            continue

        if not applied:
            print(f"  ⚠️  NO PATCH  {fname}  (pattern not matched — manual review needed)")
            skip_count += 1
            continue

        # Show diff summary
        orig_lines    = original.count("\n")
        patched_lines = patched.count("\n")
        delta         = patched_lines - orig_lines
        sign          = "+" if delta >= 0 else ""
        patches_str   = ", ".join(applied)
        print(f"  🔧 {'APPLIED' if apply else 'WOULD FIX'}  {fname:<30} "
              f"[{sign}{delta} lines]  patches: {patches_str}")

        if apply:
            bak = backup_file(path)
            path.write_text(patched, encoding="utf-8")
            print(f"             Backup → {bak.name}")

        success_count += 1

    print()
    print("── SUMMARY ─────────────────────────────────")
    print(f"   {'Applied' if apply else 'Would apply'} : {success_count} files")
    print(f"   Skipped  : {skip_count} files")
    print(f"   Errors   : {error_count} files")

    if not apply and success_count > 0:
        print()
        print("   Run with --apply to write changes to disk.")


if __name__ == "__main__":
    parser = argparse.ArgumentParser(
        description="Fix Front Desk PHP files for SPA partial-load support."
    )
    group = parser.add_mutually_exclusive_group()
    group.add_argument(
        "--apply",
        action="store_true",
        help="Write patches to disk (creates .bak backups first)",
    )
    group.add_argument(
        "--restore",
        action="store_true",
        help="Restore all files from .bak backups",
    )
    args = parser.parse_args()
    run(apply=args.apply, restore=args.restore)
