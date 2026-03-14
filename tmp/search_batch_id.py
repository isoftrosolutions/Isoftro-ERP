
import os
import re

def search_batch_id_errors(root_dir):
    pattern = re.compile(r"\[['\"]batch_id['\"]\]")
    results = []
    
    for root, dirs, files in os.walk(root_dir):
        # Skip vendor and node_modules
        if 'vendor' in dirs:
            dirs.remove('vendor')
        if 'node_modules' in dirs:
            dirs.remove('node_modules')
            
        for file in files:
            if file.endswith('.php') or file.endswith('.js'):
                path = os.path.join(root, file)
                try:
                    with open(path, 'r', encoding='utf-8', errors='ignore') as f:
                        lines = f.readlines()
                        for i, line in enumerate(lines):
                            if pattern.search(line):
                                # Check if it's guarded by isset or empty
                                if "isset" not in line and "empty" not in line and "!!" not in line:
                                    # Get context
                                    start = max(0, i - 5)
                                    end = min(len(lines), i + 5)
                                    context = "".join(lines[start:end])
                                    results.append({
                                        'file': path,
                                        'line': i + 1,
                                        'content': line.strip(),
                                        'context': context
                                    })
                except Exception as e:
                    print(f"Error reading {path}: {e}")
                    
    return results

if __name__ == "__main__":
    search_path = r"c:\Apache24\htdocs\erp"
    findings = search_batch_id_errors(search_path)
    
    print(f"Found {len(findings)} potential unguarded 'batch_id' accesses:")
    print("-" * 50)
    for f in findings:
        print(f"File: {f['file']}:{f['line']}")
        print(f"Content: {f['content']}")
        print("Context:")
        print(f"---")
        print(f['context'])
        print("-" * 50)
