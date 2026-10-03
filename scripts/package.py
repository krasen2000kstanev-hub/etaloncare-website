from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED

root = Path(__file__).resolve().parents[1]
out = root / 'output'
out.mkdir(exist_ok=True)
with ZipFile(out / 'lambda.zip', 'w', ZIP_DEFLATED) as archive:
    for folder in ['backend', 'site', 'node_modules']:
        for file in (root / folder).rglob('*'):
            if file.is_file() and (folder != 'backend' or file.suffix in ['.mjs', '.json']):
                if folder == 'node_modules' and any(part in ['playwright', 'playwright-core', '@axe-core', 'axe-core'] for part in file.parts):
                    continue
                archive.write(file, file.relative_to(root).as_posix())
print(f'Lambda package: {(out / "lambda.zip").stat().st_size} bytes')
