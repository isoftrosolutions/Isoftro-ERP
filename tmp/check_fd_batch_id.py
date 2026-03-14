
import os
import re

def search_batch_id_errors(root_dir):
    pattern = re.compile(r"\[['\"]batch_id['\"]\]")
    results = []
    
    for root, dirs, files in os.walk(root_dir):
        for file in files:
            if file.endswith('.php'):
                path = os.path.join(root, file)
                try:
                    with open(path, 'r', encoding='utf-8', errors='ignore') as f:
                        lines = f.readlines()
                        for i, line in enumerate(lines):
                            if pattern.search(line):
                                # Check if it's unguarded
                                # A guard would be: isset($var['batch_id']), !empty($var['batch_id']), $var['batch_id'] ?? ...
                                content = line.strip()
                                has_guard = "isset" in content or "empty" in content or "??" in content or "!!" in content
                                if not has_guard:
                                    # Get context
                                    start = max(0, i - 3)
                                    end = min(len(lines), i + 4)
                                    context = "".join(lines[start:end])
                                    results.append({
                                        'file': path,
                                        'line': i + 1,
                                        'content': content,
                                        'context': context
                                    })
                except Exception as e:
                    print(f"Error reading {path}: {e}")
                    
    return results

if __name__ == "__main__":
    search_path = r"c:\Apache24\htdocs\erp\app\Http\Controllers\FrontDesk"
    findings = search_batch_id_errors(search_path)
    
    for f in findings:
        print(f"File: {f['file']}:{f['line']}")
        print(f"Content: {f['content']}")
        print(f"Context:\n{f['context']}")
        print("-" * 50)
