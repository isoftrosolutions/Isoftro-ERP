import os
import re

def search_buttons(directory):
    patterns = [
        re.compile(r'onclick=["\']([^"\']*(?:renderQuickPayment|openRecordPaymentModal|goNav\([\'"]fee[\'"]).*?)["\']', re.IGNORECASE),
        re.compile(r'title=["\']Record Payment["\']', re.IGNORECASE),
        re.compile(r'title=["\']Collect Fee["\']', re.IGNORECASE)
    ]
    
    for root, dirs, files in os.walk(directory):
        if 'node_modules' in root or '.git' in root or '.gemini' in root:
            continue
        for file in files:
            if file.endswith(('.php', '.js', '.html')):
                path = os.path.join(root, file)
                try:
                    with open(path, 'r', encoding='utf-8') as f:
                        lines = f.readlines()
                        for i, line in enumerate(lines):
                            for p in patterns:
                                if p.search(line):
                                    print(f"Match in {path}:{i+1}")
                                    print(f"  Line: {line.strip()}")
                except:
                    pass

if __name__ == "__main__":
    search_buttons(r'c:\Apache24\htdocs\erp')
