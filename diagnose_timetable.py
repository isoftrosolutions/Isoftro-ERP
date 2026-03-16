import os
import re

ROOT = "c:/Apache24/htdocs/erp"

def find_in_files(directory, pattern):
    results = []
    for root, dirs, files in os.walk(directory):
        if "node_modules" in dirs: dirs.remove("node_modules")
        if "vendor" in dirs: dirs.remove("vendor")
        for file in files:
            if file.endswith((".php", ".js")):
                path = os.path.join(root, file)
                try:
                    with open(path, "r", encoding="utf-8", errors="ignore") as f:
                        content = f.read()
                        if pattern in content:
                            matches = [i+1 for i, line in enumerate(content.splitlines()) if pattern in line]
                            results.append((path, matches))
                except Exception:
                    pass
    return results

print("--- Searching for window.baseUrl ---")
for path, lines in find_in_files(ROOT, "window.baseUrl"):
    print(f"{path}: lines {lines}")

print("\n--- Searching for window.currentTenantId ---")
for path, lines in find_in_files(ROOT, "window.currentTenantId"):
    print(f"{path}: lines {lines}")

print("\n--- Searching for window.APP_URL ---")
for path, lines in find_in_files(ROOT, "window.APP_URL"):
    print(f"{path}: lines {lines}")

print("\n--- Looking at ia-timetable.js fetch calls ---")
js_path = os.path.join(ROOT, "public/assets/js/ia-timetable.js")
if os.path.exists(js_path):
    with open(js_path, "r") as f:
        lines = f.readlines()
        for i, line in enumerate(lines):
            if "fetch(" in line:
                print(f"Line {i+1}: {line.strip()}")

print("\n--- Checking web.php route /dash/ ---")
web_php = os.path.join(ROOT, "routes/web.php")
if os.path.exists(web_php):
    with open(web_php, "r") as f:
        content = f.read()
        match = re.search(r"Route::get\('/dash/\{role\}/\{page\?\}'.*?\{", content, re.DOTALL)
        if match:
            start = match.start()
            # print about 100 lines from there
            print(content[start:start+1000])

print("\n--- Checking config.php snippets ---")
config_php = os.path.join(ROOT, "config/config.php")
if os.path.exists(config_php):
    with open(config_php, "r") as f:
        print(f.read()[:500])
