import os
import re

def analyze_sql_queries(directory):
    php_files = []
    for root, dirs, files in os.walk(directory):
        if 'vendor' in root or 'node_modules' in root:
            continue
        for file in files:
            if file.endswith('.php'):
                php_files.append(os.path.join(root, file))

    issues = []
    
    # Pattern to find strings that look like SQL queries with aliases
    # We look for SELECT ... FROM ... [JOIN ...]
    sql_pattern = re.compile(r'SELECT\s+.*?\s+FROM\s+.*?(?=["\'])', re.DOTALL | re.IGNORECASE)
    
    for file_path in php_files:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
            
            # Find all strings that could be SQL
            # This is a bit rough but works for most raw SQL strings in PHP
            strings = re.findall(r'["\'](.*?)["\']', content, re.DOTALL)
            
            for s in strings:
                s_clean = s.strip()
                if s_clean.upper().startswith('SELECT'):
                    # Found a potential SELECT query
                    
                    # Check for aliases used in SELECT but not joined
                    # Common aliases: u (users), s (students), b (batches), c (courses), t (teachers)
                    aliases = ['u', 's', 'b', 'c', 't', 'fi', 'fr', 'e', 'l', 'lb', 'li']
                    
                    used_aliases = set(re.findall(r'\b([a-z]{1,2})\.\w+\b', s_clean, re.IGNORECASE))
                    
                    # Find declared aliases in FROM or JOIN
                    # Pattern: FROM table alias OR JOIN table alias
                    declared_aliases = set(re.findall(r'(?:FROM|JOIN)\s+\w+\s+(?:AS\s+)?(\b[a-z]{1,2}\b)', s_clean, re.IGNORECASE))
                    
                    # Special case: FROM table (without alias, but might be used if only one table)
                    # But if they use u.name, they MUST have declared 'u'.
                    
                    missing = used_aliases - declared_aliases
                    if missing:
                        # Log the issue
                        line_no = content.count('\n', 0, content.find(s)) + 1
                        issues.append({
                            'file': file_path,
                            'line': line_no,
                            'query': s_clean.replace('\n', ' '),
                            'missing_aliases': list(missing)
                        })

    return issues

if __name__ == "__main__":
    project_root = r'C:\Apache24\htdocs\erp'
    all_issues = analyze_sql_queries(project_root)
    
    print(f"Total issues found: {len(all_issues)}")
    for issue in all_issues:
        print(f"File: {issue['file']} (Line {issue['line']})")
        print(f"Missing Aliases: {issue['missing_aliases']}")
        print(f"Query: {issue['query'][:100]}...")
        print("-" * 40)
