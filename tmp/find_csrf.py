import os
import re

def search_pattern(directory, pattern):
    results = []
    for root, dirs, files in os.walk(directory):
        for file in files:
            if file.endswith('.php'):
                path = os.path.join(root, file)
                try:
                    with open(path, 'r', encoding='utf-8', errors='ignore') as f:
                        for i, line in enumerate(f, 1):
                            if re.search(pattern, line):
                                results.append(f"{path}:{i}: {line.strip()}")
                except Exception as e:
                    results.append(f"Error reading {path}: {str(e)}")
    return results

search_dir = 'app/Http/Controllers'
web_route = 'routes/web.php'
pattern = r'validateCsrfToken'

print(f"--- Searching for '{pattern}' ---")

# Search in controllers
found = search_pattern(search_dir, pattern)
for line in found:
    print(line)

# Search in web.php
if os.path.exists(web_route):
    with open(web_route, 'r', encoding='utf-8', errors='ignore') as f:
        for i, line in enumerate(f, 1):
            if re.search(pattern, line):
                print(f"{web_route}:{i}: {line.strip()}")

print("--- Search Complete ---")
