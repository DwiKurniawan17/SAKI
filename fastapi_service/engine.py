"""
Mesin hitung AKI/CAPEX -- sama persis dengan versi Streamlit yang sudah
divalidasi terhadap file Excel referensi PT AIM 2026 (lihat catatan
validasi di masing-masing fungsi). Modul ini murni Python, tidak
tergantung Streamlit, supaya bisa dipakai langsung oleh FastAPI service.
"""
from io import BytesIO

import numpy as np
import numpy_financial as npf
import openpyxl
from openpyxl.styles import Alignment, Border, Font, PatternFill, Side
from openpyxl.utils import get_column_letter
from scipy import stats

BLUE = Font(name="Calibri", size=10, color="0000FF")
BLACK = Font(name="Calibri", size=10, color="000000")
BOLD = Font(name="Calibri", size=10, bold=True)
TITLE = Font(name="Calibri", size=12, bold=True)
HFILL = PatternFill("solid", fgColor="D9E1F2")
YFILL = PatternFill("solid", fgColor="FFFF00")
THIN = Border(*(Side(style="thin", color="B0B0B0"),) * 4)
YEAR_COLS = ["C", "D", "E", "F", "G"]


# ========================= CALCULATION ENGINE =========================
# CATATAN VALIDASI: identik dengan modul kalkulasi di skrip Streamlit --
# hasilnya sudah dicocokkan sampai presisi floating point terhadap sheet
# "COM" / "6. Resume" file referensi PT AIM 2026 (48 bulan, tahun-1=6 bln):
#   Total Revenue=83.044.000 | Total Direct Cost=58.255.800 | GPM=29,8495%
#   Total Net Income=5.968.320,057 | NIM=7,1869% | NPV=2.164.297,047
#   MIRR=15,5435% | Payback Period=3,3851 tahun
# BUG PAJAK versi lama SUDAH DIPERBAIKI: tidak ada floor max(0, ...) pada
# perhitungan pajak, supaya identik dengan formula Excel COM!F77=EBIT*tax%
# (EBIT negatif -> pajak negatif / tax benefit).
def calculate_aki(p: dict) -> dict:
  if p.get("rdm_otc_pct") is not None:
    p["cogs_install"] = float(p["otc"] * (1.0 - float(p["rdm_otc_pct"]) / 100.0))
  if p.get("rdm_mrc_pct") is not None:
    p["cogs_monthly"] = float(p["mrc"] * (1.0 - float(p["rdm_mrc_pct"]) / 100.0))

  total_contract_months = int(p["durasi_kontrak"])
  remaining_months = total_contract_months
  months = []

  m1 = min(int(p["bulan_tahun1"]), remaining_months)
  months.append(m1)
  remaining_months -= m1
  while remaining_months > 0:
    m_cur = min(12, remaining_months)
    months.append(m_cur)
    remaining_months -= m_cur

  num_years = len(months)

  total_capex_base = float(p["material"] + p["jasa"])
  bop_val = float(total_capex_base * (p["bop_pct"] / 100.0))
  grand_capex = float(total_capex_base + bop_val)

  lifetime_months = max(1, int(p["lifetime"] * 12))
  depr_per_month = grand_capex / lifetime_months
  om_per_month = (grand_capex * (p["om_pct"] / 100.0)) / 12.0

  rows = []
  rev_arr, dc_arr, op_arr, depr_arr, ebit_arr = [], [], [], [], []
  remaining_book = grand_capex

  for idx, m in enumerate(months):
    t = idx + 1
    revenue = float((p["otc"] if t == 1 else 0) + (p["mrc"] * m))
    direct_cost = float(
        (p["cogs_install"] if t == 1 else 0) + (p["cogs_monthly"] * m)
    )
    gross_profit = revenue - direct_cost
    opex = float(om_per_month * m)
    ebitda = gross_profit - opex

    dep = min(depr_per_month * m, remaining_book)
    dep = max(0.0, dep)
    remaining_book = max(0.0, remaining_book - dep)

    ebit = ebitda - dep
    tax = ebit * (p["tax_pct"] / 100.0)  # NO floor -- match Excel exactly
    net_income = ebit - tax

    rev_arr.append(revenue)
    dc_arr.append(direct_cost)
    op_arr.append(opex)
    depr_arr.append(dep)
    ebit_arr.append(ebit)

    rows.append({
        "tahun": t,
        "bulan": m,
        "col_name": f"Tahun {t} ({m} bln)",
        "revenue": revenue,
        "direct_cost": direct_cost,
        "gross_profit": gross_profit,
        "opex": opex,
        "ebitda": ebitda,
        "depresiasi": dep,
        "ebit": ebit,
        "pajak": tax,
        "net_income": net_income,
    })

  tax_rate = p["tax_pct"] / 100.0
  ebit_after_tax = [eb * (1.0 - tax_rate) for eb in ebit_arr]

  ar_arr = [(60.0 * r) / 360.0 for r in rev_arr]
  ap_arr = [(60.0 * (d + o)) / 360.0 for d, o in zip(dc_arr, op_arr)]
  wc_arr = [ar - ap for ar, ap in zip(ar_arr, ap_arr)]

  delta_wc = []
  for i in range(num_years):
    if i == 0:
      delta_wc.append(-wc_arr[0])
    else:
      delta_wc.append(wc_arr[i - 1] - wc_arr[i])

  ncf_arr = [eat + dwc + dp for eat, dwc, dp in zip(ebit_after_tax, delta_wc, depr_arr)]

  fcf_list = []
  for i in range(num_years):
    fcf_list.append(ncf_arr[i] - grand_capex if i == 0 else ncf_arr[i])
  for i, row in enumerate(rows):
    row["free_cash_flow"] = fcf_list[i]

  cum_fcf = list(np.cumsum(fcf_list))

  wacc = p["wacc_pct"] / 100.0
  disc_arr = [1.0 / ((1.0 + wacc) ** (i + 1)) for i in range(num_years)]
  pv_fcf = [f * d for f, d in zip(fcf_list, disc_arr)]
  npv_val = sum(pv_fcf)

  has_pos = any(f > 0 for f in fcf_list)
  has_neg = any(f < 0 for f in fcf_list)
  irr_val = float(npf.mirr(fcf_list, wacc, 0.07)) if (has_pos and has_neg) else None

  if len(cum_fcf) >= 2 and cum_fcf[-1] >= 0:
    # Cek variansi cum_fcf untuk mencegah crash SciPy linregress jika nilai identik (variance = 0)
    if float(np.var(cum_fcf)) > 1e-9:
      try:
        t_years = list(range(1, num_years + 1))
        _, intercept, _, _, _ = stats.linregress(cum_fcf, t_years)
        pbp = max(0.0, float(intercept)) if not np.isnan(intercept) else 0.0
      except Exception:
        pbp = 0.0 if cum_fcf[0] >= 0 else None
    else:
      pbp = 0.0 if cum_fcf[0] >= 0 else None
  else:
    pbp = None

  total_rev = sum(rev_arr)
  total_dc = sum(dc_arr)
  total_gp = total_rev - total_dc
  total_ni = sum(r["net_income"] for r in rows)
  gpm = (total_gp / total_rev * 100) if total_rev > 0 else 0
  nim = (total_ni / total_rev * 100) if total_rev > 0 else 0

  all_pos_cashflow = all(f >= 0 for f in fcf_list) and npv_val > 0
  layak = (
      (npv_val > 0)
      and ((irr_val is not None and irr_val > (wacc + 0.02)) or all_pos_cashflow)
      and (gpm >= 7.0)
      and (nim >= 2.0)
  )

  return {
      "rows": rows,
      "months": months,
      "grand_capex": grand_capex,
      "capex_base": total_capex_base,
      "bop": bop_val,
      "total_revenue": total_rev,
      "total_direct_cost": total_dc,
      "total_gross_profit": total_gp,
      "gpm": gpm,
      "total_net_income": total_ni,
      "nim": nim,
      "npv": npv_val,
      "irr": irr_val,
      "pbp": pbp,
      "layak": layak,
      "fcf_list": fcf_list,
      "cum_fcf": [float(x) for x in cum_fcf],
  }


# ========================= GOAL SEEK ("Kurang Berapa Lagi") =========================
def goal_seek_aki(p: dict) -> dict:
  """
  Jika simulasi TIDAK LAYAK, hitung rekomendasi minimal agar layak:
    a) Minimal MRC baru (tarif bulanan)
    b) Minimal OTC baru (biaya pasang)
    c) Maksimal CAPEX (material+jasa) yang diizinkan
    d) Tambahan durasi kontrak (bulan)
  Menggunakan binary search sederhana karena calculate_aki sudah cepat.
  """
  base = calculate_aki(p)
  if base["layak"]:
    return {"needed": False, "message": "Simulasi sudah LAYAK, tidak perlu penyesuaian."}

  results = {"needed": True}
  base_capex_total = float(p.get("material", 0) + p.get("jasa", 0))

  # --- a) Minimal MRC ---
  # Jika MRC awal 0 atau kecil, rentang hi harus cukup besar berdasarkan CAPEX
  base_mrc = float(p.get("mrc", 0))
  hi_mrc = max(base_mrc * 50 + 10_000_000, (base_capex_total * 0.20) + 50_000_000)
  lo, hi = base_mrc, hi_mrc
  best_mrc = None
  for _ in range(80):
    mid = (lo + hi) / 2
    trial = dict(p, mrc=mid)
    r = calculate_aki(trial)
    if r["layak"]:
      best_mrc = mid
      hi = mid
    else:
      lo = mid
    if hi - lo < 1:
      break
  results["min_mrc"] = round(best_mrc, 0) if best_mrc else None
  results["delta_mrc"] = round(best_mrc - base_mrc, 0) if best_mrc else None

  # --- b) Minimal OTC (Dinonaktifkan: tarif OTC merupakan template standar perusahaan) ---
  results["min_otc"] = None
  results["delta_otc"] = None

  # --- c) Maksimal CAPEX (material+jasa) ---
  total_capex = base_capex_total
  if total_capex > 0:
    lo, hi = 0.0, total_capex
    best_capex = None
    for _ in range(80):
      mid = (lo + hi) / 2
      ratio = mid / total_capex if total_capex > 0 else 0
      trial = dict(p, material=p["material"] * ratio, jasa=p["jasa"] * ratio)
      r = calculate_aki(trial)
      if r["layak"]:
        best_capex = mid
        lo = mid
      else:
        hi = mid
      if hi - lo < 1:
        break
    results["max_capex"] = round(best_capex, 0) if best_capex else None
    results["delta_capex"] = round(total_capex - best_capex, 0) if best_capex else None
  else:
    results["max_capex"] = None
    results["delta_capex"] = None

  # --- d) Tambahan durasi kontrak ---
  best_dur = None
  for extra in range(1, 121):
    trial = dict(p, durasi_kontrak=int(p["durasi_kontrak"]) + extra)
    r = calculate_aki(trial)
    if r["layak"]:
      best_dur = int(p["durasi_kontrak"]) + extra
      break
  results["min_durasi"] = best_dur
  results["delta_durasi"] = extra if best_dur else None

  return results


# ========================= EXCEL EXPORT (MULTI-SHEET) =========================
# Sama persis dengan builder yang sudah diverifikasi via LibreOffice
# recalculation (0 formula error, NPV/MIRR/PBP cocok dgn Excel referensi).
def _clean_cell_str(val: any) -> str:
  s = str(val if val is not None else "")
  if s.startswith(("=", "+", "-", "@", "\t", "\r")):
    return "'" + s
  return s


def _year_headers(ws, row, year_cols, tot_col, start_col_idx=3):
  for i, col in enumerate(year_cols):
    c = ws.cell(row=row, column=start_col_idx + i, value=f"Tahun {i+1}")
    c.font, c.fill, c.border = BOLD, HFILL, THIN
    c.alignment = Alignment(horizontal="center")
  c = ws.cell(row=row, column=start_col_idx + len(year_cols), value="TOTAL")
  c.font, c.fill, c.border = BOLD, HFILL, THIN


def build_workbook(p: dict) -> openpyxl.Workbook:
  months = p["months"]
  n_years = len(months)
  num_excel_years = max(5, n_years)
  padded_months = months + [0] * (num_excel_years - n_years)

  # Dinamis: buat kolom tahun (C, D, E, F, G, ...) dan kolom TOTAL
  YEAR_COLS = [get_column_letter(3 + i) for i in range(num_excel_years)]
  TOT_COL = get_column_letter(3 + num_excel_years)
  FIRST_COL = YEAR_COLS[0]
  LAST_COL = YEAR_COLS[-1]

  wb = openpyxl.Workbook()
  wb.remove(wb.active)

  # ---- 1. Cover ----
  ws = wb.create_sheet("1. Cover")
  ws["B2"] = "SIMULASI & EVALUASI AKI / CAPEX"; ws["B2"].font = TITLE
  ws["B4"] = "Nama Proyek"; ws["C4"] = _clean_cell_str(p["nama_proyek"])
  ws["B5"] = "Lokasi"; ws["C5"] = _clean_cell_str(p.get("lokasi", ""))
  ws["B6"] = "Durasi Kontrak (Bulan)"; ws["C6"] = sum(months)
  ws["B7"] = "WACC"; ws["C7"] = p["wacc_pct"] / 100.0; ws["C7"].number_format = "0.00%"
  for r in (4, 5, 6, 7):
    ws[f"B{r}"].font = BOLD
  ws.column_dimensions["B"].width = 24; ws.column_dimensions["C"].width = 40

  # ---- 2. Rev ----
  ws = wb.create_sheet("2. Rev")
  ws["B1"] = "PERHITUNGAN REVENUE"; ws["B1"].font = TITLE
  ws["B2"] = "Nama Proyek"; ws["C2"] = f": {_clean_cell_str(p['nama_proyek'])}"
  _year_headers(ws, 4, YEAR_COLS, TOT_COL)
  ws["B4"] = "DESKRIPSI"; ws["B4"].font, ws["B4"].fill, ws["B4"].border = BOLD, HFILL, THIN
  ws["B5"] = "Jumlah Bulan"
  for i, col in enumerate(YEAR_COLS):
    cell = ws[f"{col}5"]; cell.value = padded_months[i]; cell.font = BLUE; cell.border = THIN
  ws[f"{TOT_COL}5"] = f"=SUM({FIRST_COL}5:{LAST_COL}5)"; ws[f"{TOT_COL}5"].font = BOLD
  ws["B6"] = "Tarif OTC / Pasang Baru (Rp)"
  ws["C6"] = p["otc"]; ws["C6"].font = BLUE; ws["C6"].number_format = "#,##0"
  for col in YEAR_COLS[1:]:
    ws[f"{col}6"] = 0
  ws["B7"] = "Tarif MRC / Bulanan (Rp/bulan)"
  ws["C7"] = p["mrc"]; ws["C7"].font = BLUE; ws["C7"].number_format = "#,##0"
  for col in YEAR_COLS[1:]:
    ws[f"{col}7"] = "=$C$7"
  ws["B8"] = "Revenue OTC"; ws["C8"] = "=C6"
  for col in YEAR_COLS[1:]:
    ws[f"{col}8"] = 0
  ws["B9"] = "Revenue MRC"
  for col in YEAR_COLS:
    ws[f"{col}9"] = f"={col}7*{col}5"
  ws["B10"] = "TOTAL REVENUE"; ws["B10"].font = BOLD
  for col in YEAR_COLS:
    c = ws[f"{col}10"]; c.value = f"={col}8+{col}9"; c.font = BOLD; c.number_format = "#,##0"
  ws[f"{TOT_COL}10"] = f"=SUM({FIRST_COL}10:{LAST_COL}10)"; ws[f"{TOT_COL}10"].font = BOLD; ws[f"{TOT_COL}10"].number_format = "#,##0"
  for r in range(6, 11):
    for col in YEAR_COLS + [TOT_COL]:
      ws[f"{col}{r}"].border = THIN
  ws.column_dimensions["B"].width = 32
  for col in YEAR_COLS + [TOT_COL]:
    ws.column_dimensions[col].width = 15

  # ---- 4. Dir ----
  ws = wb.create_sheet("4. Dir")
  ws["B1"] = "PERHITUNGAN DIRECT COST"; ws["B1"].font = TITLE
  _year_headers(ws, 4, YEAR_COLS, TOT_COL)
  ws["B4"] = "DESKRIPSI"; ws["B4"].font, ws["B4"].fill, ws["B4"].border = BOLD, HFILL, THIN
  ws["B5"] = "Jumlah Bulan"
  for col in YEAR_COLS:
    ws[f"{col}5"] = f"='2. Rev'!{col}5"
  ws[f"{TOT_COL}5"] = f"=SUM({FIRST_COL}5:{LAST_COL}5)"
  ws["B6"] = "COGS Direct Cost OTC (Rp)"
  ws["C6"] = p["cogs_install"]; ws["C6"].font = BLUE; ws["C6"].number_format = "#,##0"
  for col in YEAR_COLS[1:]:
    ws[f"{col}6"] = 0
  ws["B7"] = "COGS Direct Cost Bulanan (Rp/bulan)"
  ws["C7"] = p["cogs_monthly"]; ws["C7"].font = BLUE; ws["C7"].number_format = "#,##0"
  for col in YEAR_COLS[1:]:
    ws[f"{col}7"] = "=$C$7"
  ws["B8"] = "Direct Cost - Install"; ws["C8"] = "=C6"
  for col in YEAR_COLS[1:]:
    ws[f"{col}8"] = 0
  ws["B9"] = "Direct Cost - Bulanan"
  for col in YEAR_COLS:
    ws[f"{col}9"] = f"={col}7*{col}5"
  ws["B10"] = "TOTAL DIRECT COST"; ws["B10"].font = BOLD
  for col in YEAR_COLS:
    c = ws[f"{col}10"]; c.value = f"={col}8+{col}9"; c.font = BOLD; c.number_format = "#,##0"
  ws[f"{TOT_COL}10"] = f"=SUM({FIRST_COL}10:{LAST_COL}10)"; ws[f"{TOT_COL}10"].font = BOLD; ws[f"{TOT_COL}10"].number_format = "#,##0"
  for r in range(5, 11):
    for col in YEAR_COLS + [TOT_COL]:
      ws[f"{col}{r}"].border = THIN
  ws.column_dimensions["B"].width = 32
  for col in YEAR_COLS + [TOT_COL]:
    ws.column_dimensions[col].width = 15

  # ---- 3. Cpx ----
  ws = wb.create_sheet("3. Cpx")
  ws["B1"] = "PERHITUNGAN CAPEX & DEPRESIASI"; ws["B1"].font = TITLE
  ws["B3"] = "Biaya Material Jaringan (Rp)"
  ws["C3"] = p["material"]; ws["C3"].font = BLUE; ws["C3"].number_format = "#,##0"
  ws["B4"] = "Biaya Jasa Penarikan/Instalasi (Rp)"
  ws["C4"] = p["jasa"]; ws["C4"].font = BLUE; ws["C4"].number_format = "#,##0"
  ws["B5"] = "Subtotal Capex"; ws["C5"] = "=C3+C4"; ws["C5"].number_format = "#,##0"
  ws["B6"] = "Persentase BOP Lakwas (%)"
  ws["C6"] = p["bop_pct"] / 100.0; ws["C6"].font = BLUE; ws["C6"].number_format = "0.00%"
  ws["B7"] = "Nilai BOP Lakwas (Rp)"; ws["C7"] = "=C5*C6"; ws["C7"].number_format = "#,##0"
  ws["B8"] = "GRAND TOTAL CAPEX (Rp)"; ws["B8"].font = BOLD
  ws["C8"] = "=C5+C7"; ws["C8"].font = BOLD; ws["C8"].number_format = "#,##0"
  ws["B9"] = "Lifetime Aset (Tahun)"; ws["C9"] = p["lifetime"]; ws["C9"].font = BLUE
  ws["B10"] = "Lifetime Aset (Bulan)"; ws["C10"] = "=C9*12"
  ws["B11"] = "Depresiasi per Bulan (Rp)"; ws["C11"] = "=C8/C10"; ws["C11"].number_format = "#,##0"
  for r in range(3, 12):
    ws[f"B{r}"].border = THIN; ws[f"C{r}"].border = THIN
  _year_headers(ws, 13, YEAR_COLS, TOT_COL)
  ws["B13"] = "DESKRIPSI"; ws["B13"].font, ws["B13"].fill, ws["B13"].border = BOLD, HFILL, THIN
  ws["B14"] = "Jumlah Bulan"
  for col in YEAR_COLS:
    ws[f"{col}14"] = f"='2. Rev'!{col}5"
  ws[f"{TOT_COL}14"] = f"=SUM({FIRST_COL}14:{LAST_COL}14)"
  ws["B15"] = "Akumulasi Depresiasi (Awal Tahun)"; ws["C15"] = 0
  prev = "C"
  for col in YEAR_COLS[1:]:
    ws[f"{col}15"] = f"={prev}15+{prev}16"; prev = col
  ws["B16"] = "Depresiasi Tahun Berjalan"
  for col in YEAR_COLS:
    ws[f"{col}16"] = f"=MIN($C$11*{col}14,$C$8-{col}15)"
    ws[f"{col}16"].number_format = "#,##0"
  ws[f"{TOT_COL}16"] = f"=SUM({FIRST_COL}16:{LAST_COL}16)"
  ws["B17"] = "TOTAL DEPRESIASI"; ws["B17"].font = BOLD
  ws[f"{TOT_COL}17"] = f"={TOT_COL}16"; ws[f"{TOT_COL}17"].font = BOLD; ws[f"{TOT_COL}17"].number_format = "#,##0"
  for r in range(14, 17):
    for col in YEAR_COLS + [TOT_COL]:
      ws[f"{col}{r}"].border = THIN
  ws.column_dimensions["B"].width = 34
  for col in YEAR_COLS + [TOT_COL]:
    ws.column_dimensions[col].width = 15

  # ---- 5. Opx ----
  ws = wb.create_sheet("5. Opx")
  ws["B1"] = "PERHITUNGAN OPEX (O&M)"; ws["B1"].font = TITLE
  ws["B3"] = "Beban O&M (% per tahun dari CAPEX)"
  ws["C3"] = p["om_pct"] / 100.0; ws["C3"].font = BLUE; ws["C3"].number_format = "0.00%"
  ws["B4"] = "Referensi Grand Total Capex (Rp)"; ws["C4"] = "='3. Cpx'!C8"; ws["C4"].number_format = "#,##0"
  ws["B5"] = "O&M per Bulan (Rp)"; ws["C5"] = "=(C4*C3)/12"; ws["C5"].number_format = "#,##0"
  for r in range(3, 6):
    ws[f"B{r}"].border = THIN; ws[f"C{r}"].border = THIN
  _year_headers(ws, 7, YEAR_COLS, TOT_COL)
  ws["B7"] = "DESKRIPSI"; ws["B7"].font, ws["B7"].fill, ws["B7"].border = BOLD, HFILL, THIN
  ws["B8"] = "Jumlah Bulan"
  for col in YEAR_COLS:
    ws[f"{col}8"] = f"='2. Rev'!{col}5"
  ws[f"{TOT_COL}8"] = f"=SUM({FIRST_COL}8:{LAST_COL}8)"
  ws["B9"] = "OPEX (O&M) Tahun Berjalan"; ws["B9"].font = BOLD
  for col in YEAR_COLS:
    c = ws[f"{col}9"]; c.value = f"=$C$5*{col}8"; c.font = BOLD; c.number_format = "#,##0"
  ws[f"{TOT_COL}9"] = f"=SUM({FIRST_COL}9:{LAST_COL}9)"; ws[f"{TOT_COL}9"].font = BOLD; ws[f"{TOT_COL}9"].number_format = "#,##0"
  for r in (8, 9):
    for col in YEAR_COLS + [TOT_COL]:
      ws[f"{col}{r}"].border = THIN
  ws.column_dimensions["B"].width = 32
  for col in YEAR_COLS + [TOT_COL]:
    ws.column_dimensions[col].width = 15

  # ---- COM ----
  ws = wb.create_sheet("COM")
  ws["B1"] = "EVALUATION FORM / FEASIBILITY CALCULATION"; ws["B1"].font = TITLE
  _year_headers(ws, 3, YEAR_COLS, TOT_COL)
  ws["B3"] = "DESKRIPSI"; ws["B3"].font, ws["B3"].fill, ws["B3"].border = BOLD, HFILL, THIN
  ws["B4"] = "Jumlah Bulan"
  for col in YEAR_COLS:
    ws[f"{col}4"] = f"='2. Rev'!{col}5"
  ws["B5"] = "REVENUE"
  for col in YEAR_COLS:
    ws[f"{col}5"] = f"='2. Rev'!{col}10"
  ws[f"{TOT_COL}5"] = f"=SUM({FIRST_COL}5:{LAST_COL}5)"
  ws["B6"] = "DIRECT COST"
  for col in YEAR_COLS:
    ws[f"{col}6"] = f"='4. Dir'!{col}10"
  ws[f"{TOT_COL}6"] = f"=SUM({FIRST_COL}6:{LAST_COL}6)"
  ws["B7"] = "GROSS PROFIT"; ws["B7"].font = BOLD
  for col in YEAR_COLS:
    ws[f"{col}7"] = f"={col}5-{col}6"
  ws[f"{TOT_COL}7"] = f"=SUM({FIRST_COL}7:{LAST_COL}7)"; ws[f"{TOT_COL}7"].font = BOLD
  ws["B8"] = "GPM"; ws[f"{TOT_COL}8"] = f"=IFERROR({TOT_COL}7/{TOT_COL}5,0)"; ws[f"{TOT_COL}8"].number_format = "0.00%"
  ws["B9"] = "OPEX (O&M)"
  for col in YEAR_COLS:
    ws[f"{col}9"] = f"='5. Opx'!{col}9"
  ws[f"{TOT_COL}9"] = f"=SUM({FIRST_COL}9:{LAST_COL}9)"
  ws["B10"] = "EBITDA"
  for col in YEAR_COLS:
    ws[f"{col}10"] = f"={col}7-{col}9"
  ws[f"{TOT_COL}10"] = f"=SUM({FIRST_COL}10:{LAST_COL}10)"
  ws["B11"] = "DEPRESIASI"
  for col in YEAR_COLS:
    ws[f"{col}11"] = f"='3. Cpx'!{col}16"
  ws[f"{TOT_COL}11"] = f"=SUM({FIRST_COL}11:{LAST_COL}11)"
  ws["B12"] = "EBIT"; ws["B12"].font = BOLD
  for col in YEAR_COLS:
    ws[f"{col}12"] = f"={col}10-{col}11"
  ws[f"{TOT_COL}12"] = f"=SUM({FIRST_COL}12:{LAST_COL}12)"; ws[f"{TOT_COL}12"].font = BOLD
  ws["B13"] = "EBIT MARGIN"; ws[f"{TOT_COL}13"] = f"=IFERROR({TOT_COL}12/{TOT_COL}5,0)"; ws[f"{TOT_COL}13"].number_format = "0.00%"
  ws["B15"] = "Tarif Pajak Perusahaan (%)"
  ws["C15"] = p["tax_pct"] / 100.0; ws["C15"].font = BLUE; ws["C15"].number_format = "0.00%"
  ws["B16"] = "PAJAK"
  for col in YEAR_COLS:
    ws[f"{col}16"] = f"={col}12*$C$15"
  ws[f"{TOT_COL}16"] = f"=SUM({FIRST_COL}16:{LAST_COL}16)"
  ws["B17"] = "NET INCOME"; ws["B17"].font = BOLD
  for col in YEAR_COLS:
    c = ws[f"{col}17"]; c.value = f"={col}12-{col}16"; c.font = BOLD
  ws[f"{TOT_COL}17"] = f"=SUM({FIRST_COL}17:{LAST_COL}17)"; ws[f"{TOT_COL}17"].font = BOLD
  ws["B18"] = "NI MARGIN (NIM)"; ws[f"{TOT_COL}18"] = f"=IFERROR({TOT_COL}17/{TOT_COL}5,0)"; ws[f"{TOT_COL}18"].number_format = "0.00%"
  ws["B20"] = "Working Capital Assumptions"; ws["B20"].font = BOLD
  ws["B21"] = "Account Receivable (Hari)"; ws["C21"] = 60; ws["C21"].font = BLUE
  ws["B22"] = "Account Payable (Hari)"; ws["C22"] = 60; ws["C22"].font = BLUE
  ws["B23"] = "Account Receivable"
  for col in YEAR_COLS:
    ws[f"{col}23"] = f"=($C$21*{col}5)/360"
  ws["B24"] = "Account Payable"
  for col in YEAR_COLS:
    ws[f"{col}24"] = f"=($C$22*({col}6+{col}9))/360"
  ws["B25"] = "Working Capital"
  for col in YEAR_COLS:
    ws[f"{col}25"] = f"={col}23-{col}24"
  ws["B26"] = "WC (Incr)/Decr"; ws["C26"] = "=-C25"
  prev = "C"
  for col in YEAR_COLS[1:]:
    ws[f"{col}26"] = f"={prev}25-{col}25"; prev = col
  ws["B28"] = "EBIT setelah Pajak"
  for col in YEAR_COLS:
    ws[f"{col}28"] = f"={col}12*(1-$C$15)"
  ws["B29"] = "Net Cash Flow"
  for col in YEAR_COLS:
    ws[f"{col}29"] = f"={col}28+{col}26+{col}11"
  ws["B31"] = "Investasi (CAPEX)"; ws["C31"] = "=-'3. Cpx'!C8"
  for col in YEAR_COLS[1:]:
    ws[f"{col}31"] = 0
  ws["B32"] = "FREE CASH FLOW"; ws["B32"].font = BOLD
  for col in YEAR_COLS:
    c = ws[f"{col}32"]; c.value = f"={col}29+{col}31"; c.font = BOLD; c.number_format = "#,##0"
  ws["B34"] = "WACC / Discount Rate (%)"
  ws["C34"] = p["wacc_pct"] / 100.0; ws["C34"].font = BLUE; ws["C34"].number_format = "0.00%"
  ws["B35"] = "Discounted Years"; ws["C35"] = 1
  prev = "C"
  for col in YEAR_COLS[1:]:
    ws[f"{col}35"] = f"={prev}35+1"; prev = col
  ws["B36"] = "Discount Factor"
  for col in YEAR_COLS:
    ws[f"{col}36"] = f"=1/((1+$C$34)^{col}35)"
  ws["B37"] = "PV Free Cash Flow"
  for col in YEAR_COLS:
    ws[f"{col}37"] = f"={col}32*{col}36"
  ws[f"{TOT_COL}37"] = f"=SUM({FIRST_COL}37:{LAST_COL}37)"
  ws["B39"] = "NET PRESENT VALUE (NPV)"; ws["B39"].font = BOLD
  ws["C39"] = f"=SUM({FIRST_COL}37:{LAST_COL}37)"; ws["C39"].font = BOLD; ws["C39"].number_format = "#,##0"
  ws["B40"] = "Reinvestment Rate (utk MIRR)"
  ws["C40"] = 0.07; ws["C40"].font = BLUE; ws["C40"].number_format = "0.00%"
  ws["B41"] = "MIRR"; ws["B41"].font = BOLD
  ws["C41"] = f"=MIRR({FIRST_COL}32:{LAST_COL}32,$C$34,$C$40)"; ws["C41"].font = BOLD; ws["C41"].number_format = "0.00%"
  ws["B43"] = "Akumulasi FCF"; ws["C43"] = "=C32"
  prev = "C"
  for col in YEAR_COLS[1:]:
    ws[f"{col}43"] = f"={prev}43+{col}32"; prev = col
  ws["B44"] = "PAYBACK PERIOD (Tahun)"; ws["B44"].font = BOLD
  ws["C44"] = f"=INTERCEPT({FIRST_COL}35:{LAST_COL}35,{FIRST_COL}43:{LAST_COL}43)"; ws["C44"].font = BOLD; ws["C44"].number_format = "0.00"
  ws["B47"] = "KRITERIA KELAYAKAN"; ws["B47"].font = BOLD
  ws["B48"] = "Verdict"
  ws["C48"] = (
      f'=IF(AND(C39>0,C41>($C$34+0.02),{TOT_COL}8>=0.07,{TOT_COL}18>=0.02),'
      '"LAYAK INVESTASI","TIDAK LAYAK INVESTASI")'
  )
  ws["C48"].font = BOLD
  ws.column_dimensions["B"].width = 34
  for col in YEAR_COLS + [TOT_COL]:
    ws.column_dimensions[col].width = 15

  # ---- 6. Resume ----
  ws = wb.create_sheet("6. Resume")
  ws["B2"] = "Nama Project"; ws["B2"].font = TITLE
  ws["C2"] = ": Pengadaan Layanan Internet"; ws["C2"].font = TITLE
  ws["B3"] = "Nama Pelanggan"; ws["B3"].font = TITLE
  ws["C3"] = f": {_clean_cell_str(p['nama_proyek'])}"; ws["C3"].font = TITLE
  ws["B4"] = "Durasi Kontrak"; ws["B4"].font = TITLE
  tot_m = sum(months)
  ws["C4"] = f": {tot_m // 12} Tahun {tot_m % 12} Bulan"; ws["C4"].font = TITLE

  top_headers = ["Revenue", "DIR", "Gross Profit", "GPM", "Net Income", "NIM", "NPV", "MIRR", "Payback Period"]
  for i, h in enumerate(top_headers):
    col = get_column_letter(2 + i)
    c = ws[f"{col}6"]; c.value = h; c.font = BOLD; c.fill = HFILL; c.border = THIN
    c.alignment = Alignment(horizontal="center")
  formula_map = [f"=COM!{TOT_COL}5", f"=COM!{TOT_COL}6", f"=COM!{TOT_COL}7", f"=COM!{TOT_COL}8", f"=COM!{TOT_COL}17",
                 f"=COM!{TOT_COL}18", "=COM!C39", "=COM!C41", "=COM!C44"]
  fmt_map = ["#,##0", "#,##0", "#,##0", "0.00%", "#,##0", "0.00%", "#,##0", "0.00%", "0.00"]
  for i, (f, fmt) in enumerate(zip(formula_map, fmt_map)):
    col = get_column_letter(2 + i)
    c = ws[f"{col}7"]; c.value = f; c.number_format = fmt; c.border = THIN
    c.alignment = Alignment(horizontal="right")

  ws["B9"] = "Analisa Kelayakan Investasi (dalam Rp, kecuali angka persentase)"; ws["B9"].font = TITLE
  for i, col in enumerate(YEAR_COLS):
    c = ws[f"{col}10"]; c.value = f"Tahun {i+1}"; c.font = BOLD; c.fill = HFILL; c.border = THIN
    c.alignment = Alignment(horizontal="center")
  ws[f"{TOT_COL}10"] = "TOTAL"; ws[f"{TOT_COL}10"].font = BOLD; ws[f"{TOT_COL}10"].fill = HFILL; ws[f"{TOT_COL}10"].border = THIN

  pl_rows = [
      (11, "Revenue", "COM!{c}5", f"COM!{TOT_COL}5", "#,##0"),
      (12, "Direct Cost", "COM!{c}6", f"COM!{TOT_COL}6", "#,##0"),
      (13, "Gross Margin", "COM!{c}7", f"COM!{TOT_COL}7", "#,##0"),
      (15, "OPEX (O&M)", "COM!{c}9", f"COM!{TOT_COL}9", "#,##0"),
      (16, "Depresiasi", "COM!{c}11", f"COM!{TOT_COL}11", "#,##0"),
      (17, "Tax", "COM!{c}16", f"COM!{TOT_COL}16", "#,##0"),
      (18, "Net Income", "COM!{c}17", f"COM!{TOT_COL}17", "#,##0"),
      (20, "CAPEX", "COM!{c}31", "COM!C39", "#,##0"),
  ]
  for r_idx, label, cell_tmpl, tot_ref, fmt in pl_rows:
    cL = ws.cell(row=r_idx, column=2, value=label); cL.font = BOLD; cL.border = THIN
    for i, col in enumerate(YEAR_COLS):
      cell = ws.cell(row=r_idx, column=3 + i)
      cell.value = f"={cell_tmpl.format(c=col)}"
      cell.number_format = fmt
      cell.border = THIN
    cT = ws.cell(row=r_idx, column=3 + num_excel_years)
    cT.value = "=-COM!C31" if label == "CAPEX" else f"={tot_ref}"
    cT.number_format = fmt; cT.font = BOLD; cT.border = THIN

  ws["B14"] = "GP Margin"; ws[f"{TOT_COL}14"] = f"=COM!{TOT_COL}8"; ws[f"{TOT_COL}14"].number_format = "0.00%"
  ws["B19"] = "NI Margin"; ws[f"{TOT_COL}19"] = f"=COM!{TOT_COL}18"; ws[f"{TOT_COL}19"].number_format = "0.00%"

  ws["C23"] = "• NPV ="; ws["D23"] = "=COM!C39"; ws["D23"].number_format = "#,##0"; ws["D23"].font = BOLD
  ws["C24"] = "• MIRR ="; ws["D24"] = "=COM!C41"; ws["D24"].number_format = "0.00%"; ws["D24"].font = BOLD
  ws["C25"] = "• PP ="; ws["D25"] = "=COM!C44"; ws["D25"].number_format = "0.00"; ws["D25"].font = BOLD

  # Dynamic placement for the right summary card (at least column L or 2 columns after TOTAL)
  right_start_idx = max(12, 3 + num_excel_years + 2)
  col_L = get_column_letter(right_start_idx)
  col_M = get_column_letter(right_start_idx + 1)
  col_N = get_column_letter(right_start_idx + 2)

  ws[f"{col_L}10"] = f"Simulasi Perhitungan Usulan Capex Delivery {_clean_cell_str(p['nama_proyek'])}"
  ws[f"{col_L}10"].font = BOLD; ws[f"{col_L}10"].fill = PatternFill("solid", fgColor="F2F2F2")
  ws.merge_cells(f"{col_L}10:{col_M}10")
  ws[f"{col_L}10"].alignment = Alignment(horizontal="center", wrap_text=True)

  right_rows = [
      (12, "Revenue", "=B7", "#,##0", None),
      (13, "Direct Cost", "=C7", "#,##0", None),
      (14, "Gross Profit", "=D7", "#,##0", None),
      (15, "GPM", "=E7", "0.00%", "Minimal 7%"),
      (16, "Capex (incl BoP Lakwas)", "='3. Cpx'!C8", "#,##0", None),
      (17, "Net Income", "=F7", "#,##0", None),
      (18, "NIM", "=G7", "0.00%", "Minimal 2%"),
      (19, "NPV", "=H7", "#,##0", None),
      (20, "IRR (MIRR)", "=I7", "0.00%", f"Minimal {(p['wacc_pct']+2):.2f}%"),
      (21, "Payback Period", "=J7", "0.00", None),
      (22, "Verdict Kelayakan", "=COM!C48", None, None),
  ]
  for r_idx, lbl, formula, fmt, note in right_rows:
    cL = ws.cell(row=r_idx, column=right_start_idx, value=lbl); cL.font = BOLD
    cL.fill = PatternFill("solid", fgColor="F2F2F2"); cL.border = THIN
    cM = ws.cell(row=r_idx, column=right_start_idx + 1, value=formula)
    if fmt:
      cM.number_format = fmt
    cM.fill = PatternFill("solid", fgColor="F2F2F2"); cM.border = THIN
    cM.alignment = Alignment(horizontal="right")
    if note:
      ws.cell(row=r_idx, column=right_start_idx + 2, value=note)

  ws[f"{col_L}24"] = f"WACC yang dipakai: {p['wacc_pct']:.2f}%."
  ws[f"{col_L}24"].fill = YFILL
  ws[f"{col_L}25"] = "IRR minimal = WACC + 2%"
  ws[f"{col_L}25"].fill = YFILL

  col_widths = {"A": 4, "B": 20, col_L: 26, col_M: 20, col_N: 16}
  for cl, w in col_widths.items():
    ws.column_dimensions[cl].width = w
  for cl in YEAR_COLS + [TOT_COL]:
    ws.column_dimensions[cl].width = 15

  return wb


def export_excel_bytes(p: dict, summary: dict) -> bytes:
  full_params = dict(p)
  full_params["months"] = summary["months"]
  wb = build_workbook(full_params)
  buf = BytesIO()
  wb.save(buf)
  return buf.getvalue()