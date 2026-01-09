#!/usr/bin/env python3
"""
Update all GLPI 11 references to GLPI 10 in the glpi-10 branch
"""
import os
import re
from pathlib import Path

# Base directory
base_dir = Path('/home/diego/glpi11/plugins/flowbpmn')

# Files to update
patterns_to_replace = [
    # Version references
    (r'GLPI 11\.0\+', 'GLPI 10.0+'),
    (r'GLPI 11\.x', 'GLPI 10.x'),
    (r'GLPI 11', 'GLPI 10'),
    (r'glpi 11', 'glpi 10'),
    
    # PHP version requirements
    (r'PHP 8\.1\+', 'PHP 7.4+'),
    (r'PHP 8\.1', 'PHP 7.4'),
    
    # Bootstrap version
    (r'Bootstrap 5\.x', 'Bootstrap 4.x'),
    (r'Bootstrap 5', 'Bootstrap 4'),
    
    # Specific mentions
    (r'Removido suporte ao GLPI 10\.x', 'Suporte para GLPI 10.x'),
    (r'requer exclusivamente GLPI 11', 'requer GLPI 10'),
]

# File extensions to process
extensions = ['.md', '.php', '.txt']

# Files to skip
skip_files = ['CHANGELOG.md']  # Keep changelog as historical record

def update_file(file_path):
    """Update a single file with all replacements"""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original_content = content
        
        # Apply all replacements
        for pattern, replacement in patterns_to_replace:
            content = re.sub(pattern, replacement, content)
        
        # Only write if content changed
        if content != original_content:
            with open(file_path, 'w', encoding='utf-8') as f:
                f.write(content)
            return True
        return False
    except Exception as e:
        print(f"Error processing {file_path}: {e}")
        return False

# Process all files
updated_files = []
for ext in extensions:
    for file_path in base_dir.rglob(f'*{ext}'):
        # Skip if in skip list
        if file_path.name in skip_files:
            print(f"⊘ Skipped: {file_path.relative_to(base_dir)}")
            continue
        
        # Update file
        if update_file(file_path):
            updated_files.append(file_path)
            print(f"✓ Updated: {file_path.relative_to(base_dir)}")

print(f"\n{'='*60}")
print(f"Total files updated: {len(updated_files)}")
print(f"{'='*60}")

# List all updated files
if updated_files:
    print("\nUpdated files:")
    for f in updated_files:
        print(f"  - {f.relative_to(base_dir)}")
