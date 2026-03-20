
import re

file_path = r'c:\Apache24\htdocs\erp\public\assets\js\ia-students.js'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

print("--- Searching for renderStudentProfile ---")
matches = re.finditer(r'renderStudentProfile', content)
for m in matches:
    line_no = content.count('\n', 0, m.start()) + 1
    # Context
    start_line = content.rfind('\n', 0, m.start()) + 1
    end_line = content.find('\n', m.end())
    context = content[start_line:end_line].strip()
    print(f"Line {line_no}: {context}")
