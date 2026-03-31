import os

def replace_public_assets(directory):
    for root, dirs, files in os.walk(directory):
        for file in files:
            if file.endswith('.php') or file.endswith('.blade.php'):
                filepath = os.path.join(root, file)
                with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
                    content = f.read()
                
                new_content = content.replace('/public/assets', '/assets')
                
                if new_content != content:
                    with open(filepath, 'w', encoding='utf-8') as f:
                        f.write(new_content)
                    print(f"Updated: {filepath}")

if __name__ == "__main__":
    target_dir = r'c:\Apache24\htdocs\erp\resources\views'
    replace_public_assets(target_dir)
