"""
FastAPI service untuk mesin hitung AKI/CAPEX.

Jalankan lokal:
    pip install -r requirements.txt
    uvicorn main:app --host 0.0.0.0 --port 8001 --reload

Endpoint:
    POST /api/calculate       -> JSON hasil hitungan + goal seek (jika tidak layak)
    POST /api/export-excel    -> file .xlsx (multi-sheet, formula-driven)
    GET  /health              -> health check untuk load balancer / docker healthcheck
"""
import os

from engine import calculate_aki, export_excel_bytes, goal_seek_aki
from word_export import build_evaluasi_word
from fastapi import FastAPI, Header, HTTPException
from fastapi.responses import Response
from pydantic import BaseModel, Field

APP_API_KEY = os.environ.get("APP_API_KEY", "kenko-paper-clips")  # isi di .env, kosong = auth dimatikan (dev only)

app = FastAPI(title="AKI/CAPEX Calculation Service", version="1.2.0")


class SimulasiInput(BaseModel):
  nama_proyek: str = "Proyek Baru"
  lokasi: str = ""
  paketisasi: str | None = None
  bandwidth: str | None = None
  durasi_kontrak: int = Field(48, ge=1, le=120)
  bulan_tahun1: int = Field(6, ge=1, le=12)
  otc: float = 0
  mrc: float = 0
  cogs_install: float = 0
  cogs_monthly: float = 0
  rdm_otc_pct: float | None = None
  rdm_mrc_pct: float | None = None
  material: float = 0
  jasa: float = 0
  bop_pct: float = 0.40
  lifetime: int = Field(4, ge=1, le=20)
  om_pct: float = 12.0
  tax_pct: float = 22.0
  wacc_pct: float = 10.9


def _check_key(x_api_key: str | None):
  if APP_API_KEY and x_api_key != APP_API_KEY:
    raise HTTPException(status_code=401, detail="Invalid or missing X-API-KEY")


@app.get("/health")
def health():
  return {"status": "ok"}


@app.post("/api/calculate")
def calculate(payload: SimulasiInput, x_api_key: str | None = Header(default=None)):
  _check_key(x_api_key)
  p = payload.model_dump()
  summary = calculate_aki(p)

  # Jika TIDAK LAYAK, otomatis jalankan goal seek
  if not summary["layak"]:
    summary["goal_seek"] = goal_seek_aki(p)
  else:
    summary["goal_seek"] = {"needed": False}

  return summary


@app.post("/api/export-excel")
def export_excel(payload: SimulasiInput, x_api_key: str | None = Header(default=None)):
  _check_key(x_api_key)
  p = payload.model_dump()
  summary = calculate_aki(p)
  xlsx_bytes = export_excel_bytes(p, summary)
  filename = f"Resume_AKI_{payload.nama_proyek.replace(' ', '_')}.xlsx"
  return Response(
      content=xlsx_bytes,
      media_type="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
      headers={"Content-Disposition": f'attachment; filename="{filename}"'},
  )


@app.post("/api/export-word")
def export_word(payload: SimulasiInput, x_api_key: str | None = Header(default=None)):
  _check_key(x_api_key)
  p = payload.model_dump()
  summary = calculate_aki(p)
  docx_bytes = build_evaluasi_word(p, summary)
  filename = f"Evaluasi_AKI_{payload.nama_proyek.replace(' ', '_')}.docx"
  return Response(
      content=docx_bytes,
      media_type="application/vnd.openxmlformats-officedocument.wordprocessingml.document",
      headers={"Content-Disposition": f'attachment; filename="{filename}"'},
  )
