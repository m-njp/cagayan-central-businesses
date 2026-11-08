"""Entry point for the Composer scripts: creates the venv on first use, then runs the parser or its tests."""

import os
import subprocess
import sys
import venv
from pathlib import Path

HERE = Path(__file__).resolve().parent
ENV = HERE / ".venv"
PYTHON = ENV / ("Scripts/python.exe" if os.name == "nt" else "bin/python")


def main() -> int:
    if not PYTHON.exists():
        print("Creating tools/coa/.venv and installing tools/coa/requirements.txt ...")
        venv.create(ENV, with_pip=True)
        subprocess.run([PYTHON, "-m", "pip", "install", "--quiet", "-r", HERE / "requirements.txt"], check=True)
    command = sys.argv[1:] or ["extract"]
    if command[0] == "test":
        arguments = ["-m", "pytest", *command[1:]]
    elif command[0] == "intake":
        arguments = ["-u", "-m", "coa.intake", *command[1:]]
    else:
        arguments = ["-m", "coa", *command]
    environment = {**os.environ, "PYTHONPATH": str(HERE), "PYTHONIOENCODING": "utf-8"}
    return subprocess.run([PYTHON, *arguments], cwd=HERE, env=environment).returncode


if __name__ == "__main__":
    sys.exit(main())
