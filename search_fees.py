
import re

file_path = r'c:\Apache24\htdocs\erp\public\assets\js\frontdesk\fd-fees.js'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Search for patterns like:
# .onsubmit = ...
# .addEventListener('submit', ...)
# action: 'record_payment'

print("--- Searching for .onsubmit ---")
onsubmits = re.finditer(r'([a-zA-Z0-9_-]+)\.onsubmit\s*=', content)
for m in onsubmits:
    line_no = content.count('\n', 0, m.start()) + 1
    print(f"Line {line_no}: {m.group(0)}")

print("\n--- Searching for addEventListener('submit' ---")
listeners = re.finditer(r'addEventListener\(\'submit\'', content)
for m in listeners:
    line_no = content.count('\n', 0, m.start()) + 1
    print(f"Line {line_no}: {m.group(0)}")

print("\n--- Searching for record_payment ---")
actions = re.finditer(r'action\s*:\s*[\'"]record_payment[\'"]', content)
for m in actions:
    line_no = content.count('\n', 0, m.start()) + 1
    # Get context (line)
    start_line = content.rfind('\n', 0, m.start()) + 1
    end_line = content.find('\n', m.end())
    context = content[start_line:end_line].strip()
    print(f"Line {line_no}: {context}")

print("\n--- Searching for record_bulk_payment ---")
bulk_actions = re.finditer(r'action\s*:\s*[\'"]record_bulk_payment[\'"]', content)
for m in bulk_actions:
    line_no = content.count('\n', 0, m.start()) + 1
    print(f"Line {line_no}: record_bulk_payment")
