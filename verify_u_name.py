import os
import re

directory = r'c:\Apache24\htdocs\erp\app\Http\Controllers\FrontDesk'
results = []

# Regex to find SELECT queries with u.name
query_pattern = re.compile(r'SELECT\s+.*?\bu\.name\b.*?\s+FROM\s+(?!\w+\s+(\w+\s+)?u)', re.DOTALL | re.IGNORECASE)

# Actually, the join might be "JOIN users u" which the negative lookahead above tries to catch if not present.
# But it's tricky with multiline and subqueries.

# Let's try a different approach:
# 1. Find all strings that have "u.name"
# 2. Check if that same string or the surrounding code (assignment block) has "JOIN users u" or "FROM users u"

for filename in os.listdir(directory):
    if filename.endswith('.php'):
        path = os.path.join(directory, filename)
        with open(path, 'r', encoding='utf-8', errors='ignore') as f:
            lines = f.readlines()
            for i, line in enumerate(lines):
                if 'u.name' in line.lower():
                    # Look back and forward 10 lines to find the join
                    context = "".join(lines[max(0, i-10):min(len(lines), i+10)])
                    if not re.search(r'(JOIN|FROM)\s+users\s+(\w+\s+)?u', context, re.IGNORECASE):
                        results.append((filename, i+1, line.strip(), context))

if results:
    for res in results:
        print(f"File: {res[0]} Line: {res[1]}")
        print(f"Line Content: {res[2]}")
        # print(f"Context:\n{res[3]}\n")
else:
    print("No obvious missing joins for u.name found.")
