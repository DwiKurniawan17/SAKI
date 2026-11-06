"""
Word document (.docx) export – HASIL EVALUASI AKI CAPEX PT. TELKOM.
Generates a .docx that closely matches the official Telkom internal
evaluation template. Fields that can be auto-filled from the simulation
are pre-populated; the rest are left blank for manual input.
"""
from io import BytesIO
from datetime import datetime

from docx import Document
from docx.shared import Cm, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.oxml.ns import nsdecls
from docx.oxml import parse_xml


# ========================= HELPERS =========================

def _shade(cell, hex_color: str):
    """Set cell background / shading colour."""
    shading = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{hex_color}"/>')
    cell._tc.get_or_add_tcPr().append(shading)


def _borders(cell, sz=4, color="000000"):
    """Apply thin solid borders to all four sides of a cell."""
    xml = (
        f'<w:tcBorders {nsdecls("w")}>'
        f'<w:top w:val="single" w:sz="{sz}" w:space="0" w:color="{color}"/>'
        f'<w:bottom w:val="single" w:sz="{sz}" w:space="0" w:color="{color}"/>'
        f'<w:left w:val="single" w:sz="{sz}" w:space="0" w:color="{color}"/>'
        f'<w:right w:val="single" w:sz="{sz}" w:space="0" w:color="{color}"/>'
        f'</w:tcBorders>'
    )
    cell._tc.get_or_add_tcPr().append(parse_xml(xml))


def _v_align(cell, val="center"):
    """Set vertical alignment inside a cell ('top', 'center', 'bottom')."""
    tcPr = cell._tc.get_or_add_tcPr()
    tcPr.append(parse_xml(f'<w:vAlign {nsdecls("w")} w:val="{val}"/>'))


def _text(cell, txt, bold=False, italic=False, size=8.5, align=None, font="Arial", color=None):
    """Write formatted text into a cell, replacing any existing content."""
    cell.text = ""
    lines = str(txt if txt is not None else "").split("\n")
    for i, line in enumerate(lines):
        p = cell.paragraphs[0] if i == 0 else cell.add_paragraph()
        if align is not None:
            p.alignment = align
        p.paragraph_format.space_before = Pt(0.5)
        p.paragraph_format.space_after = Pt(0.5)
        p.paragraph_format.line_spacing = 1.0
        run = p.add_run(line)
        run.font.size = Pt(size)
        run.font.bold = bold
        run.font.italic = italic
        run.font.name = font
        if color:
            run.font.color.rgb = color


def _merge(table, r1, c1, r2, c2):
    """Merge a rectangular range of cells and return the merged cell."""
    return table.cell(r1, c1).merge(table.cell(r2, c2))


def _set_row_height(cell, twips=300):
    """Set minimum row height (1 cm ≈ 567 twips)."""
    tr = cell._tc.getparent()
    trPr = tr.get_or_add_trPr()
    trPr.append(parse_xml(
        f'<w:trHeight {nsdecls("w")} w:val="{twips}" w:hRule="atLeast"/>'
    ))


def _spacer(doc, pt=3):
    """Insert a tiny paragraph spacer."""
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(pt)
    run = p.add_run()
    run.font.size = Pt(1)


# ---- Formatting helpers (Indonesian locale) ----

def _rp(val):
    """Format a number as 'Rp xx.xxx.xxx'."""
    if val is None or val == 0:
        return ""
    s = f"{abs(val):,.0f}".replace(",", ".")
    return f"Rp {'- ' if val < 0 else ''}{s}"


def _num(val):
    """Format a number with Indonesian thousand separators (dot)."""
    if val is None:
        return ""
    return f"{val:,.0f}".replace(",", ".")


def _pct(val):
    """Format as percentage with comma decimal (Indonesian)."""
    if val is None:
        return "N/A"
    return f"{val:.2f}%".replace(".", ",")


def _dur(months):
    """Convert total months to 'X Tahun Y Bulan' string."""
    y, m = divmod(int(months), 12)
    return f"{y} Tahun {m} Bulan"


# ========================= MAIN BUILDER =========================

def build_evaluasi_word(params: dict, summary: dict) -> bytes:
    """
    Build the 'HASIL EVALUASI AKI CAPEX PT. TELKOM' Word document.
    """
    doc = Document()

    # ---- Page setup (A4, compact margins to guarantee 1 page) ----
    sec = doc.sections[0]
    sec.page_width = Cm(21.0)
    sec.page_height = Cm(29.7)
    sec.top_margin = Cm(1.0)
    sec.bottom_margin = Cm(1.0)
    sec.left_margin = Cm(1.2)
    sec.right_margin = Cm(1.2)

    year = datetime.now().year

    # ---- Extract data from params & summary ----
    nama        = params.get("nama_proyek", "")
    lokasi      = params.get("lokasi", "")
    material    = float(params.get("material", 0))
    jasa        = float(params.get("jasa", 0))
    durasi      = int(params.get("durasi_kontrak", 48))

    capex_base  = float(summary.get("capex_base", material + jasa))
    bop_val     = float(summary.get("bop", 0))
    total_rev   = float(summary.get("total_revenue", 0))
    total_dc    = float(summary.get("total_direct_cost", 0))
    total_gp    = float(summary.get("total_gross_profit", 0))
    gpm         = float(summary.get("gpm", 0))
    total_opex  = sum(r.get("opex", 0) for r in summary.get("rows", []))
    total_ni    = float(summary.get("total_net_income", 0))
    nim         = float(summary.get("nim", 0))
    npv_val     = float(summary.get("npv", 0))
    irr_raw     = summary.get("irr")
    pbp_raw     = summary.get("pbp")
    layak       = summary.get("layak", False)

    durasi_str  = _dur(durasi)
    irr_pct     = (irr_raw * 100) if irr_raw is not None else None

    if pbp_raw is not None and pbp_raw > 0:
        pbp_months = round(pbp_raw * 12)
        pbp_str = f"{_dur(pbp_months)} atau {pbp_months} Bulan"
    else:
        pbp_str = "N/A"

    # ================================================================
    # 1. TITLE BOX
    # ================================================================
    tt = doc.add_table(rows=1, cols=1)
    tt.alignment = WD_TABLE_ALIGNMENT.CENTER
    tc = tt.cell(0, 0)
    tc.width = Cm(18.6)
    _borders(tc, sz=6)
    _text(tc, f"HASIL EVALUASI AKI CAPEX  PT. TELKOM  TAHUN  {year}",
          bold=True, size=10, align=WD_ALIGN_PARAGRAPH.CENTER)
    tc.paragraphs[0].paragraph_format.space_before = Pt(4)
    tc.paragraphs[0].paragraph_format.space_after = Pt(4)

    _spacer(doc, pt=2)

    # ================================================================
    # 2. MAIN EVALUATION TABLE (Unified 4 columns)
    # ================================================================
    # Col 0: 5.2 cm (Main label)
    # Col 1: 5.5 cm (Sub-label / Kinerja metric name)
    # Col 2: 0.5 cm (Colon :)
    # Col 3: 7.4 cm (Value)
    # Total width: 5.2 + 5.5 + 0.5 + 7.4 = 18.6 cm
    W_COLS = [Cm(5.2), Cm(5.5), Cm(0.5), Cm(7.4)]

    TOTAL_ROWS = 34
    tbl = doc.add_table(rows=TOTAL_ROWS, cols=4)
    tbl.alignment = WD_TABLE_ALIGNMENT.CENTER

    # Apply column widths & borders to all cells
    for row in tbl.rows:
        for ci, w in enumerate(W_COLS):
            row.cells[ci].width = w
            _borders(row.cells[ci], sz=4)

    # Row 0: USULAN PROGRAM PP
    m = _merge(tbl, 0, 0, 0, 1)
    _text(m, "USULAN PROGRAM PP", bold=True, size=8.5)
    _text(tbl.cell(0, 2), ":", size=8.5)
    _text(tbl.cell(0, 3), "", size=8.5)

    # Row 1: ID Proposal
    m = _merge(tbl, 1, 0, 1, 1)
    _text(m, "ID Proposal", size=8.5)
    _text(tbl.cell(1, 2), ":", size=8.5)
    _text(tbl.cell(1, 3), "", size=8.5)

    # Row 2: Nama Program
    m = _merge(tbl, 2, 0, 2, 1)
    _text(m, "Nama Program", size=8.5)
    _text(tbl.cell(2, 2), ":", size=8.5)
    _text(tbl.cell(2, 3), nama, size=8.5)

    # Row 3: Jumlah LOP
    m = _merge(tbl, 3, 0, 3, 1)
    _text(m, "Jumlah LOP", size=8.5)
    _text(tbl.cell(3, 2), ":", size=8.5)
    _text(tbl.cell(3, 3), "1", size=8.5)

    # Row 4: ID LOP (sesuai jumlah LOP)
    m = _merge(tbl, 4, 0, 4, 1)
    _text(m, "ID LOP (sesuai jumlah LOP)", size=8.5)
    _text(tbl.cell(4, 2), ":", size=8.5)
    _text(tbl.cell(4, 3), "", size=8.5)

    # Row 5: Lokasi / Site Program
    m = _merge(tbl, 5, 0, 5, 1)
    _text(m, "Lokasi / Site Program", size=8.5)
    _text(tbl.cell(5, 2), ":", size=8.5)
    _text(tbl.cell(5, 3), lokasi, size=8.5)

    # Rows 6-7: Kapasitas
    m_kap = _merge(tbl, 6, 0, 7, 0)
    _text(m_kap, "Kapasitas", size=8.5)
    _v_align(m_kap, "center")
    _text(tbl.cell(6, 1), "VOLUME", size=8.5, align=WD_ALIGN_PARAGRAPH.RIGHT)
    _text(tbl.cell(6, 2), ":", size=8.5)
    _text(tbl.cell(6, 3), "1", size=8.5)

    _text(tbl.cell(7, 1), "SATUAN", size=8.5, align=WD_ALIGN_PARAGRAPH.RIGHT)
    _text(tbl.cell(7, 2), ":", size=8.5)
    _text(tbl.cell(7, 3), "Lokasi", size=8.5)

    # Row 8: Penanggung Jawab
    m = _merge(tbl, 8, 0, 8, 1)
    _text(m, "Penanggung Jawab", size=8.5)
    _text(tbl.cell(8, 2), ":", size=8.5)
    _text(tbl.cell(8, 3), "", size=8.5)

    # Row 9: Nomor Account
    m = _merge(tbl, 9, 0, 9, 1)
    _text(m, "Nomor Account", size=8.5)
    _text(tbl.cell(9, 2), ":", size=8.5)
    _text(tbl.cell(9, 3), "", size=8.5)

    # Rows 10-11: Nilai Program (CAPEX & BOP)
    m_prog = _merge(tbl, 10, 0, 11, 0)
    _text(m_prog, "Nilai Program", size=8.5)
    _v_align(m_prog, "center")
    _text(tbl.cell(10, 1), "CAPEX", size=8.5)
    _text(tbl.cell(10, 2), ":", size=8.5)
    _text(tbl.cell(10, 3), _rp(capex_base), bold=True, size=8.5, align=WD_ALIGN_PARAGRAPH.RIGHT)

    _text(tbl.cell(11, 1), "BOP LAKWAS", size=8.5)
    _text(tbl.cell(11, 2), ":", size=8.5)
    _text(tbl.cell(11, 3), _rp(bop_val), bold=True, size=8.5, align=WD_ALIGN_PARAGRAPH.RIGHT)

    # Row 12: Capex / Line
    m = _merge(tbl, 12, 0, 12, 1)
    _text(m, "Capex / Line", size=8.5)
    _text(tbl.cell(12, 2), ":", size=8.5)
    _text(tbl.cell(12, 3), "", size=8.5)

    # Row 13: Waktu Penyelesaian
    _text(tbl.cell(13, 0), "Waktu Penyelesaian", size=8.5)
    _text(tbl.cell(13, 1), "bulan", size=8.5)
    _text(tbl.cell(13, 2), ":", size=8.5)
    _text(tbl.cell(13, 3), "1", size=8.5)

    # Row 14: Rencana Selesai
    m = _merge(tbl, 14, 0, 14, 1)
    _text(m, "Rencana Selesai", size=8.5)
    _text(tbl.cell(14, 2), ":", size=8.5)
    _text(tbl.cell(14, 3), str(year), size=8.5)

    # Row 15: Masa kontrak/berlangganan
    _text(tbl.cell(15, 0), "Masa kontrak/berlangganan", size=8.5)
    _text(tbl.cell(15, 1), "tahun", size=8.5)
    _text(tbl.cell(15, 2), ":", size=8.5)
    _text(tbl.cell(15, 3), durasi_str, size=8.5)

    # Row 16: Pemilihan Teknologi
    m = _merge(tbl, 16, 0, 16, 1)
    _text(m, "Pemilihan Teknologi", size=8.5)
    _text(tbl.cell(16, 2), ":", size=8.5)
    _text(tbl.cell(16, 3), "FO", size=8.5)

    # Row 17: Usulan pola kemitraan
    m = _merge(tbl, 17, 0, 17, 1)
    _text(m, "Usulan pola kemitraan", size=8.5)
    _text(tbl.cell(17, 2), ":", size=8.5)
    _text(tbl.cell(17, 3), "-", size=8.5)

    # Row 18: Alasan keberadaan proyek
    m = _merge(tbl, 18, 0, 18, 1)
    _text(m, "Alasan keberadaan proyek", size=8.5)
    _text(tbl.cell(18, 2), ":", size=8.5)
    _text(tbl.cell(18, 3), "", size=8.5)

    # ================================================================
    # KINERJA BISNIS (Rows 19 to 29)
    # ================================================================
    m_kinerja = _merge(tbl, 19, 0, 29, 0)
    _text(m_kinerja, "Kinerja Bisnis", bold=True, size=9)
    _v_align(m_kinerja, "center")

    fin_items = [
        ("Estimasi Revenue",           _rp(total_rev),                              False),
        ("Masa Kontrak / berlangganan", f"{durasi_str} atau {durasi} Bulan",         False),
        ("Nilai COGS / Direct Cost",   _num(total_dc),                              True),
        ("Nilai Opex",                 _rp(total_opex),                             False),
        ("Gross Profit",               _rp(total_gp),                               False),
        ("Gross Profit Margin",        _pct(gpm),                                   False),
        ("Net Income",                 _rp(total_ni),                               False),
        ("Net Income Margin",          _pct(nim),                                   False),
        ("IRR",                        _pct(irr_pct),                               False),
        ("NPV",                        _rp(npv_val),                                False),
        ("Payback Periode",            pbp_str,                                     False),
    ]

    for idx, (metric, val, is_plain_num) in enumerate(fin_items):
        r_idx = 19 + idx
        _text(tbl.cell(r_idx, 1), metric, italic=True, size=8.5, align=WD_ALIGN_PARAGRAPH.RIGHT)
        _text(tbl.cell(r_idx, 2), ":", size=8.5)
        _text(tbl.cell(r_idx, 3), val, bold=True, size=8.5, align=WD_ALIGN_PARAGRAPH.RIGHT)

    # ================================================================
    # KRITERIA INVESTASI (Row 30)
    # ================================================================
    m_crit = _merge(tbl, 30, 0, 30, 1)
    _text(m_crit, "KRITERIA INVESTASI UNTUK PERIODE PEMAKAIAN", bold=True, size=8.5)
    _text(tbl.cell(30, 2), ":", size=8.5)

    cell_layak = tbl.cell(30, 3)
    verdict = "LAYAK" if layak else "TIDAK LAYAK"
    _text(cell_layak, verdict, bold=True, size=9.5, align=WD_ALIGN_PARAGRAPH.CENTER)
    _shade(cell_layak, "70AD47" if layak else "ED7D31")

    # ================================================================
    # CATATAN PENTING (Rows 31-33)
    # ================================================================
    m_cat = _merge(tbl, 31, 0, 31, 1)
    _text(m_cat, "Catatan penting", size=8.5)
    _text(tbl.cell(31, 2), ":", size=8.5)
    _text(tbl.cell(31, 3), "", size=8.5)

    # Blank row 1
    m_b1 = _merge(tbl, 32, 0, 32, 3)
    _text(m_b1, "", size=8.5)
    _set_row_height(m_b1, twips=240)

    # Blank row 2
    m_b2 = _merge(tbl, 33, 0, 33, 3)
    _text(m_b2, "", size=8.5)
    _set_row_height(m_b2, twips=240)

    _spacer(doc, pt=3)

    # ================================================================
    # 3. TABEL TANDA TANGAN (Signature block - 4 columns)
    # ================================================================
    SIG_COLS = [Cm(3.5), Cm(6.5), Cm(4.3), Cm(4.3)]
    st = doc.add_table(rows=5, cols=4)
    st.alignment = WD_TABLE_ALIGNMENT.CENTER

    for row in st.rows:
        for ci, w in enumerate(SIG_COLS):
            row.cells[ci].width = w
            _borders(row.cells[ci], sz=4)

    # Header Row
    _text(st.cell(0, 0), "", size=8.5)
    _text(st.cell(0, 1), "NAMA / NIK / JABATAN", bold=True, size=8.5, align=WD_ALIGN_PARAGRAPH.CENTER)
    _text(st.cell(0, 2), "TANGGAL", bold=True, size=8.5, align=WD_ALIGN_PARAGRAPH.CENTER)
    _text(st.cell(0, 3), "TTD", bold=True, size=8.5, align=WD_ALIGN_PARAGRAPH.CENTER)

    # Row 1: DIBUAT OLEH (Account Manager)
    c_role1 = st.cell(1, 0)
    _text(c_role1, "DIBUAT OLEH", bold=True, size=8.5)
    _v_align(c_role1, "center")

    c_name1 = st.cell(1, 1)
    _text(c_name1, "\nNIK.\nACCOUNT MANAGER\n", bold=True, size=8.5, align=WD_ALIGN_PARAGRAPH.CENTER)
    _shade(c_name1, "FFFF00")
    _v_align(c_name1, "center")
    _set_row_height(c_name1, twips=650)

    # Row 2: DIPERIKSA OLEH (Manager Marketing)
    c_role2 = st.cell(2, 0)
    _text(c_role2, "DIPERIKSA OLEH", bold=True, size=8.5)
    _v_align(c_role2, "center")

    c_name2 = st.cell(2, 1)
    _text(c_name2, "\nNIK.\nMANAGER MARKETING\n", bold=True, size=8.5, align=WD_ALIGN_PARAGRAPH.CENTER)
    _shade(c_name2, "FFFF00")
    _v_align(c_name2, "center")
    _set_row_height(c_name2, twips=650)

    # Row 3: DIPERIKSA OLEH (Mgr Access Design)
    c_role3 = st.cell(3, 0)
    _text(c_role3, "DIPERIKSA OLEH", bold=True, size=8.5)
    _v_align(c_role3, "center")

    c_name3 = st.cell(3, 1)
    _text(c_name3, "\nNIK.\nMGR ACCESS DESIGN & CAPEX\nMANAGEMENT II REGIONAL II\n", bold=True, size=8.5, align=WD_ALIGN_PARAGRAPH.CENTER)
    _shade(c_name3, "FFFF00")
    _v_align(c_name3, "center")
    _set_row_height(c_name3, twips=700)

    # Row 4: DISETUJUI OLEH (Dimas Syahputra)
    c_role4 = st.cell(4, 0)
    _text(c_role4, "DISETUJUI OLEH", bold=True, size=8.5)
    _v_align(c_role4, "center")

    c_name4 = st.cell(4, 1)
    _text(c_name4, "Dimas Syahputra\nNIK. 80020\nMANAGER SOLUTION & OFFERING II\nREGIONAL II", bold=True, size=8.5, align=WD_ALIGN_PARAGRAPH.CENTER)
    _shade(c_name4, "FFFF00")
    _v_align(c_name4, "center")
    _set_row_height(c_name4, twips=700)

    # Save to bytes
    buf = BytesIO()
    doc.save(buf)
    return buf.getvalue()
