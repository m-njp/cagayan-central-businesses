"""Entry point for the Composer scripts: creates the venv on first use, then runs the parser or its tests."""

import os
import subprocess
import sys
from tempfile import TemporaryDirectory
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
    elif command[0] == "collect":
        arguments = ["-u", "-m", "coa.collection", *command[1:]]
    elif command[0] == "collect-workbooks":
        arguments = ["-u", "-m", "coa.workbook_collection", *command[1:]]
    elif command[0] == "audit":
        arguments = ["-u", "-m", "coa.audit", *command[1:]]
    elif command[0] == "sample":
        arguments = ["-u", "-m", "coa.sample", *command[1:]]
    else:
        arguments = ["-m", "coa", *command]
    environment = {**os.environ, "PYTHONPATH": str(HERE), "PYTHONIOENCODING": "utf-8"}
    workingDirectory = HERE.parents[1] if command[0] in {"collect", "collect-workbooks"} else HERE
    if command[0] == "test" and not any(argument == "--basetemp" or argument.startswith("--basetemp=") for argument in command[1:]):
        temporaryRoot = HERE.parents[1] / "storage/app/source-review/coa"
        temporaryRoot.mkdir(parents=True, exist_ok=True)
        with TemporaryDirectory(prefix=".test-", dir=temporaryRoot) as temporary:
            return subprocess.run([PYTHON, *arguments, "--basetemp", temporary], cwd=workingDirectory, env=environment).returncode
    return subprocess.run([PYTHON, *arguments], cwd=workingDirectory, env=environment).returncode


if __name__ == "__main__":
    sys.exit(main())
