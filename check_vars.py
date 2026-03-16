import os

root = "c:/Apache24/htdocs/erp"

files = [
    "resources/views/admin/timetable.php",
    "public/assets/js/ia-timetable.js",
    "public/assets/js/ia-core.js",
    "routes/web.php"
]

for f in files:
    path = os.path.join(root, f)
    if os.path.exists(path):
        print(f"--- {f} ---")
        with open(path, "r", encoding="utf-8", errors="ignore") as file:
            content = file.read()
            # check for baseUrl or APP_URL usage
            lines = content.splitlines()
            for i, line in enumerate(lines):
                if "baseUrl" in line or "APP_URL" in line or "tenant_id" in line:
                    print(f"{i+1}: {line.strip()}")
    else:
        print(f"--- {f} NOT FOUND ---")
