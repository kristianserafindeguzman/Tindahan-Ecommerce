from fastapi import FastAPI, HTTPException
import subprocess
import sys
import os
import json

app = FastAPI(title="Tindahan ML API")

PROJECT_ROOT = os.path.abspath(
    os.path.join(os.path.dirname(__file__), "..", "..")
)

ML_DIR = os.path.join(PROJECT_ROOT, "ml")


@app.get("/")
def root():
    return {
        "service": "Tindahan ML API",
        "status": "online"
    }


@app.get("/health")
def health():
    return {
        "status": "healthy"
    }


@app.post("/predict/demand")
def predict_demand(store_id: int | None = None):

    script = os.path.join(
        ML_DIR,
        "random_forest_demand.py"
    )

    command = [
        sys.executable,
        script,
        "--mode=predict",
        "--output=json",
    ]

    if store_id is not None:
        command.append(f"--store_id={store_id}")

    result = subprocess.run(
        command,
        cwd=PROJECT_ROOT,
        capture_output=True,
        text=True,
        timeout=120
    )

    if result.returncode != 0:
        raise HTTPException(
            status_code=500,
            detail=result.stderr or result.stdout
        )

    try:
        return json.loads(result.stdout)
    except Exception:
        raise HTTPException(
            status_code=500,
            detail=result.stdout
        )


@app.post("/train/demand")
def train_demand(store_id: int | None = None):

    script = os.path.join(
        ML_DIR,
        "random_forest_demand.py"
    )

    command = [
        sys.executable,
        script,
        "--mode=train",
        "--output=json",
    ]

    if store_id is not None:
        command.append(f"--store_id={store_id}")

    result = subprocess.run(
        command,
        cwd=PROJECT_ROOT,
        capture_output=True,
        text=True,
        timeout=300
    )

    if result.returncode != 0:
        raise HTTPException(
            status_code=500,
            detail=result.stderr or result.stdout
        )

    try:
        return json.loads(result.stdout)
    except Exception:
        raise HTTPException(
            status_code=500,
            detail=result.stdout
        )


@app.post("/predict/personalization")
def predict_personalization():

    script = os.path.join(
        ML_DIR,
        "random_forest_personalization.py"
    )

    result = subprocess.run(
        [
            sys.executable,
            script,
            "--mode=predict",
        ],
        cwd=PROJECT_ROOT,
        capture_output=True,
        text=True,
        timeout=120
    )

    if result.returncode != 0:
        raise HTTPException(
            status_code=500,
            detail=result.stderr or result.stdout
        )

    try:
        return json.loads(result.stdout)
    except Exception:
        raise HTTPException(
            status_code=500,
            detail=result.stdout
        )


@app.post("/train/personalization")
def train_personalization():

    script = os.path.join(
        ML_DIR,
        "random_forest_personalization.py"
    )

    result = subprocess.run(
        [
            sys.executable,
            script,
            "--mode=train",
        ],
        cwd=PROJECT_ROOT,
        capture_output=True,
        text=True,
        timeout=300
    )

    if result.returncode != 0:
        raise HTTPException(
            status_code=500,
            detail=result.stderr or result.stdout
        )

    try:
        return json.loads(result.stdout)
    except Exception:
        raise HTTPException(
            status_code=500,
            detail=result.stdout
        )
