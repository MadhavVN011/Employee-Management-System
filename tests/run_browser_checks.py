#!/usr/bin/env python3
"""Exercise disposable Apache/MariaDB services in a real Chrome browser."""
import json
import os
import pathlib
import shutil
import socket
import subprocess
import tempfile

ROOT = pathlib.Path(__file__).resolve().parents[1]


def main():
    chrome = next((shutil.which(name) for name in ['google-chrome', 'chromium', 'chromium-browser'] if shutil.which(name)), None)
    if not chrome or not shutil.which('npm'):
        raise RuntimeError('These browser checks require Chrome/Chromium, Node.js, npm, and Docker Compose.')
    directory = pathlib.Path(tempfile.mkdtemp(prefix='ems-browser-'))
    with socket.socket() as listener:
        listener.bind(('127.0.0.1', 0))
        port = listener.getsockname()[1]
    settings = {'port': port, 'chrome': chrome}
    settings_file = directory / 'settings.json'
    settings_file.write_text(json.dumps(settings))
    override = directory / 'compose.yaml'
    override.write_text('services:\n  app:\n    image: employee-management-system-app\n')
    compose = ['docker', 'compose', '-p', directory.name, '-f', str(ROOT / 'compose.yaml'), '-f', str(override)]
    env = dict(os.environ, APP_PORT=str(port), NODE_PATH=str(directory / 'node_modules'))

    def run(args, **kwargs):
        result = subprocess.run(args, cwd=ROOT, env=env, capture_output=True, text=True, **kwargs)
        if result.returncode:
            raise RuntimeError(result.stdout + result.stderr)
        return result.stdout

    try:
        run(['npm', 'install', '--prefix', str(directory), '--no-audit', '--no-fund', 'playwright@1.65.1'])
        run(['docker', 'compose', 'build', 'app'])
        run(compose + ['up', '-d', '--no-build', '--wait', '--wait-timeout', '120'])
        # Simulate an old executable upload in this test volume only. Apache must
        # deny it even though new uploads can no longer create files like it.
        run(compose + ['exec', '-T', '--user', 'www-data', 'app', 'php', '-r', "file_put_contents('/var/www/html/images/legacy-audit.php', '<?php echo \"bad\"; ?>');"])
        result = subprocess.run(['node', str(ROOT / 'tests/browser_checks.cjs'), str(settings_file)], cwd=ROOT, env=env)
        if result.returncode:
            raise RuntimeError('Browser regression checks failed')
    finally:
        subprocess.run(compose + ['down', '-v'], cwd=ROOT, env=env, capture_output=True)
        shutil.rmtree(directory)


if __name__ == '__main__':
    main()
