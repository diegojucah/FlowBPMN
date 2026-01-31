#!/usr/bin/env python3
"""
Complete update of README.md for GLPI 10 compatibility
"""

file_path = '/home/diego/glpi11/plugins/flowbpmn/README.md'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# All replacements
replacements = [
    # Badge
    ('GLPI-11.0+-orange', 'GLPI-10.0+-orange'),
    
    # Requirements section
    ('**GLPI**: 11.0.0 or higher', '**GLPI**: 10.0.0 or higher'),
    ('**PHP**: 7.4 or higher (8.1+ recommended)', '**PHP**: 7.4 or higher'),
    
    # Any remaining GLPI 11 references
    ('GLPI 11', 'GLPI 10'),
    ('glpi 11', 'glpi 10'),
    
    # PHP 8.1 recommendations
    ('8.1+', '7.4+'),
    ('PHP 8.1', 'PHP 7.4'),
]

for old, new in replacements:
    content = content.replace(old, new)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("✓ README.md updated successfully")
print("\nChanges made:")
for old, new in replacements:
    if old in content or new in content:
        print(f"  - {old} → {new}")
