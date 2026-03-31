import os

def fix_paths_in_all_php_files(root_dir):
    extensions = ('.php', '.js', '.css')
    exclude_dirs = ['vendor', 'node_modules', '.git']
    
    for root, dirs, files in os.walk(root_dir):
        # Skip excluded directories
        dirs[:] = [d for d in dirs if d not in exclude_dirs]
        
        for file in files:
            if file.endswith(extensions):
                filepath = os.path.join(root, file)
                try:
                    with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
                        content = f.read()
                    
                    # Core fix: replace /public/ with /
                    # We have to be careful not to replace parts of file paths (filesystem), only URLs.
                    # In this project, it seems mostly strings in code.
                    # Typical pattern: '/public/assets' -> '/assets'
                    # Also handles: '/public/uploads' -> '/uploads'
                    
                    new_content = content.replace('/public/assets', '/assets')
                    new_content = new_content.replace('/public/uploads', '/uploads')
                    new_content = new_content.replace('/public/build', '/build') # Vite
                    
                    if new_content != content:
                        with open(filepath, 'w', encoding='utf-8') as f:
                            f.write(new_content)
                        print(f"Updated: {filepath}")
                except Exception as e:
                    print(f"Error processing {filepath}: {e}")

if __name__ == "__main__":
    fix_paths_in_all_php_files('.')
