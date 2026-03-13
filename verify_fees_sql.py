import os
import re

path = r'c:\Apache24\htdocs\erp\app\Http\Controllers\FrontDesk\fees.php'
with open(path, 'r', encoding='utf-8', errors='ignore') as f:
    lines = f.readlines()
    for i, line in enumerate(lines):
        if 'u.name' in line.lower():
            # context = "".join(lines[max(0, i-5):min(len(lines), i+5)])
            print(f"Line {i+1}: {line.strip()}")
            # Find the start of the query (the assignment)
            j = i
            while j > 0 and '$query' not in lines[j] and '$stmt' not in lines[j] and 'SELECT' not in lines[j].upper():
                j -= 1
            # Find the end of the query (the execution or semicolon)
            k = i
            while k < len(lines) and ';' not in lines[k]:
                k += 1
            query_context = "".join(lines[j:k+1])
            print(f"Query Context:\n{query_context}\n")
            if 'JOIN users u' not in query_context and 'FROM users u' not in query_context:
                print(">>> WARNING: MISSING JOIN identified in this snippet! <<<\n")
