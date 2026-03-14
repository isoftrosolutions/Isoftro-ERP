
import os
import re

def find_specific_error(root_dir):
    pattern = re.compile(r"\[['\"]batch_id['\"]\]")
    for root, dirs, files in os.walk(root_dir):
        for file in files:
            if file.endswith('.php'):
                path = os.path.join(root, file)
                try:
                    with open(path, 'r', encoding='utf-8', errors='ignore') as f:
                        lines = f.readlines()
                        for i, line in enumerate(lines):
                            if pattern.search(line):
                                # If it's not guarded
                                if "isset" not in line and "empty" not in line and "??" not in line:
                                    print(f"Potential Error: {path}:{i+1}")
                                    print(f"Line: {line.strip()}")
                                    # Look for the query above it
                                    start = max(0, i - 15)
                                    context = "".join(lines[start:i+1])
                                    print("Context Above:")
                                    print(context)
                                    print("-" * 40)
                except:
                    pass

if __name__ == "__main__":
    find_specific_error(r"c:\Apache24\htdocs\erp\app")
