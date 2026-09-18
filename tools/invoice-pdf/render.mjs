#!/usr/bin/env node
/**
 * SNS invoice PDF — port of sns-erp-backend/src/documents/render-pdf.ts (PDFKit).
 * Reads document JSON from stdin, writes PDF bytes to stdout.
 */
import crypto from "node:crypto";
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";
import PDFDocument from "pdfkit";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const FONT_REG = path.join(__dirname, "fonts", "NotoSans-Regular.ttf");
const FONT_BOLD = path.join(__dirname, "fonts", "NotoSans-Bold.ttf");
const FONT_ETH = path.join(__dirname, "fonts", "NotoSansEthiopic-Regular.ttf");
const LOGO = path.join(__dirname, "assets", "sns-logo.png");

const PAGE = {
  width: 595.28,
  height: 841.89,
  marginLeft: 36,
  marginRight: 36,
};
const TABLE_COLS = {
  lineNo: 26,
  name: 200,
  unit: 40,
  qty: 52,
  pieces: 46,
  unitPrice: 72,
  total: 87,
};
const CONTENT_WIDTH = PAGE.width - PAGE.marginLeft - PAGE.marginRight;
const PAGE_BOTTOM = PAGE.height - 28;

const DOC_TYPE_PROFILE = {
  PROFORMA: {
    title: "Proforma Invoice",
    showValidUntil: false,
    showAgainstInvoice: false,
    requireAmountInWords: false,
    negateAmounts: false,
  },
  SALES_ORDER: {
    title: "Sales Order",
    showValidUntil: false,
    showAgainstInvoice: false,
    requireAmountInWords: false,
    negateAmounts: false,
  },
  TAX_INVOICE: {
    title: "Proforma Invoice",
    showValidUntil: false,
    showAgainstInvoice: false,
    requireAmountInWords: true,
    negateAmounts: false,
  },
  CREDIT_NOTE: {
    title: "Credit Note",
    showValidUntil: false,
    showAgainstInvoice: true,
    requireAmountInWords: true,
    negateAmounts: true,
  },
};

function roundMoney(n) {
  return Math.round((Number(n) + Number.EPSILON) * 100) / 100;
}

function formatMoney(value, negate = false) {
  let n = roundMoney(value);
  if (negate) n = -n;
  const neg = n < 0;
  const abs = Math.abs(n);
  const [intPart, frac = "00"] = abs.toFixed(2).split(".");
  const withSep = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  const body = `${withSep}.${frac}`;
  return neg ? `(${body})` : body;
}

function formatQty(value) {
  return Number(value).toFixed(2);
}

function formatDocDate(iso) {
  const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(iso || "").trim());
  if (!m) return String(iso || "");
  const d = new Date(Date.UTC(+m[1], +m[2] - 1, +m[3]));
  return d.toLocaleDateString("en-US", {
    weekday: "short",
    month: "short",
    day: "numeric",
    year: "numeric",
    timeZone: "UTC",
  });
}

const ONES = [
  "", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine",
  "Ten", "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen",
  "Sixteen", "Seventeen", "Eighteen", "Nineteen",
];
const TENS = ["", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];

function underThousand(n) {
  if (n === 0) return "";
  if (n < 20) return ONES[n];
  if (n < 100) {
    const t = Math.floor(n / 10);
    const o = n % 10;
    return o ? `${TENS[t]}-${ONES[o]}` : TENS[t];
  }
  const h = Math.floor(n / 100);
  const rest = n % 100;
  return rest ? `${ONES[h]} Hundred ${underThousand(rest)}` : `${ONES[h]} Hundred`;
}

function integerToWords(n) {
  if (n === 0) return "Zero";
  const parts = [];
  let remaining = n;
  for (const [scale, label] of [
    [1_000_000_000, "Billion"],
    [1_000_000, "Million"],
    [1_000, "Thousand"],
  ]) {
    if (remaining >= scale) {
      parts.push(`${underThousand(Math.floor(remaining / scale))} ${label}`);
      remaining %= scale;
    }
  }
  if (remaining > 0) parts.push(underThousand(remaining));
  return parts.join(" ");
}

function etbAmountInWords(amount) {
  const money = roundMoney(Math.abs(Number(amount)));
  const birr = Math.floor(money);
  const santim = Math.round((money - birr) * 100);
  const birrWords = integerToWords(birr);
  if (santim === 0) return `${birrWords} Birr Only`;
  return `${birrWords} Birr and ${integerToWords(santim)} Santim Only`;
}

function formatLineDescription(raw) {
  const trimmed = String(raw ?? "").trim();
  if (!trimmed) return "";
  if (!(trimmed.startsWith("{") && trimmed.endsWith("}"))) return trimmed;
  try {
    const parsed = JSON.parse(trimmed);
    if (!parsed || typeof parsed !== "object" || Array.isArray(parsed)) return trimmed;
    return Object.entries(parsed)
      .map(([key, value]) => {
        const label = key.replace(/_/g, " ");
        if (value == null) return `${label}: —`;
        if (typeof value === "object") return `${label}: ${JSON.stringify(value)}`;
        return `${label}: ${String(value)}`;
      })
      .join(" · ");
  } catch {
    return trimmed;
  }
}

function buildViewModel(input) {
  const profile = DOC_TYPE_PROFILE[input.doc_type] || DOC_TYPE_PROFILE.PROFORMA;
  const negate = profile.negateAmounts;
  const discountLabel =
    (input.discount?.type || "PERCENT") === "PERCENT"
      ? `${input.discount?.value ?? "0"}% Discount`
      : "Discount";
  return {
    profile,
    docNumber: input.doc_number || "",
    docDate: formatDocDate(input.doc_date),
    validUntil: input.valid_until ? formatDocDate(input.valid_until) : null,
    parentDocNumber: input.parent_doc_number ?? null,
    supplier: input.supplier || {},
    customer: input.customer || {},
    lines: (input.lines || []).map((l, i) => ({
      lineNo: String(l.line_no ?? i + 1),
      name: l.name || "",
      description: formatLineDescription(l.description),
      uom: l.uom_code || "",
      qty: formatQty(l.quantity ?? 0),
      pieces: l.unit_count == null || l.unit_count === "" ? "" : String(l.unit_count),
      unitPrice: formatMoney(l.unit_price ?? 0, negate),
      lineTotal: formatMoney(l.line_total ?? 0, negate),
    })),
    discountLabel,
    discountDisplay: formatMoney(input.discount?.amount ?? 0, true),
    taxLabel: `VAT ${input.tax?.rate ?? "15"}%`,
    taxDisplay: formatMoney(input.tax?.amount ?? 0, negate),
    subtotalDisplay: formatMoney(input.totals?.subtotal ?? 0, negate),
    afterDiscountDisplay: formatMoney(input.totals?.after_discount ?? 0, negate),
    grandTotalDisplay: formatMoney(input.totals?.grand_total ?? 0, negate),
    amountInWords: profile.requireAmountInWords
      ? etbAmountInWords(input.totals?.grand_total ?? 0)
      : null,
    notes: (input.notes || []).map(String).filter((n) => n.trim() !== ""),
    terms: input.terms || {},
    preparedBy: input.prepared_by || { name: "", phone: "" },
    approvedBy: input.approved_by || { name: "", phone: "" },
  };
}

function pdfDateFromDocDate(docDate) {
  const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(docDate || "");
  if (m) return `D:${m[1]}${m[2]}${m[3]}000000Z`;
  return "D:19700101000000Z";
}

function freezePdfId(buf, canonicalJson) {
  const hash = crypto.createHash("sha256").update(canonicalJson).digest();
  const idHex = Buffer.concat([hash.subarray(0, 16), hash.subarray(16, 32)])
    .toString("hex")
    .toUpperCase();
  const replacement = `/ID [<${idHex.slice(0, 32)}><${idHex.slice(32, 64)}>]`;
  let text = buf.toString("latin1");
  const idRe = /\/ID\s*\[\s*<[0-9A-Fa-f]+>\s*<[0-9A-Fa-f]+>\s*\]/;
  if (idRe.test(text)) text = text.replace(idRe, replacement);
  else text = text.replace(/trailer\s*<</, `trailer\n<<\n${replacement}\n`);
  return Buffer.from(text, "latin1");
}

const COLS = [
  { key: "lineNo", width: TABLE_COLS.lineNo, align: "center" },
  { key: "name", width: TABLE_COLS.name, align: "left" },
  { key: "unit", width: TABLE_COLS.unit, align: "center" },
  { key: "qty", width: TABLE_COLS.qty, align: "right" },
  { key: "pieces", width: TABLE_COLS.pieces, align: "center" },
  { key: "unitPrice", width: TABLE_COLS.unitPrice, align: "right" },
  { key: "total", width: TABLE_COLS.total, align: "right" },
];

function measureNameCell(doc, name, description, width, compact) {
  const nameSize = compact ? 7 : 8;
  const descSize = compact ? 6.5 : 7.5;
  const pad = compact ? 4 : 6;
  doc.font("Bold").fontSize(nameSize);
  const nameH = doc.heightOfString(name || " ", { width: width - 6 });
  doc.font("Regular").fontSize(descSize);
  const descH = description ? doc.heightOfString(description, { width: width - 6 }) + 1 : 0;
  return Math.max(compact ? 14 : 18, nameH + descH + pad);
}

async function renderPdf(input) {
  const vm = buildViewModel(input);
  const creationDate = pdfDateFromDocDate(input.doc_date);

  const doc = new PDFDocument({
    size: "A4",
    margins: {
      top: 24,
      bottom: 24,
      left: PAGE.marginLeft,
      right: PAGE.marginRight,
    },
    autoFirstPage: true,
    bufferPages: true,
    info: {
      Title: `${vm.profile.title} ${vm.docNumber}`,
      Author: "SNS Furniture Manufacturing",
      Subject: vm.profile.title,
      Creator: "SNS ERP Document Renderer",
      Producer: "SNS ERP Document Renderer",
      CreationDate: new Date(`${input.doc_date}T00:00:00.000Z`),
      ModDate: new Date(`${input.doc_date}T00:00:00.000Z`),
    },
  });

  doc._info = { ...doc._info, CreationDate: creationDate, ModDate: creationDate };
  doc.registerFont("Regular", FONT_REG);
  doc.registerFont("Bold", FONT_BOLD);
  doc.registerFont("Ethiopic", FONT_ETH);
  doc.addPage = (() => doc);

  const chunks = [];
  const done = new Promise((resolve, reject) => {
    doc.on("data", (c) => chunks.push(c));
    doc.on("end", () => resolve(Buffer.concat(chunks)));
    doc.on("error", reject);
  });

  function resetCursor(y) {
    doc.x = PAGE.marginLeft;
    doc.y = y;
  }

  function textAt(text, x, y, opts = {}) {
    const font = opts.font ?? "Regular";
    const size = opts.size ?? 8;
    const fill = opts.fill ?? "#111";
    const { font: _f, size: _s, fill: _fill, ...textOpts } = opts;
    const width = textOpts.width ?? CONTENT_WIDTH;
    const lineBreak = textOpts.lineBreak ?? true;
    doc.font(font).fontSize(size).fillColor(fill);
    const measured = lineBreak
      ? doc.heightOfString(text || " ", { width, ...textOpts })
      : size * 1.15;
    const drawOpts = { ...textOpts, width, lineBreak };
    if (lineBreak && drawOpts.height == null) drawOpts.height = measured + size;
    doc.text(text || " ", x, y, drawOpts);
    const nextY = y + Math.max(measured, size * 1.15);
    resetCursor(nextY);
    return nextY;
  }

  function drawHeader() {
    const logoTop = 10;
    const logoLeft = 18;
    const logoH = 78;
    const rightW = 220;
    const rightX = PAGE.width - PAGE.marginRight - rightW;

    if (fs.existsSync(LOGO)) {
      doc.image(LOGO, logoLeft, logoTop, { height: logoH });
      resetCursor(logoTop);
    }

    textAt(vm.profile.title, rightX, logoTop + 8, {
      width: rightW, align: "right", font: "Bold", size: 18, lineBreak: false,
    });
    textAt(`Invoice Id  ${vm.docNumber}`, rightX, logoTop + 32, {
      width: rightW, align: "right", font: "Regular", size: 9, lineBreak: false,
    });
    textAt(`Date  ${vm.docDate}`, rightX, logoTop + 46, {
      width: rightW, align: "right", font: "Regular", size: 9, lineBreak: false,
    });
    if (vm.profile.showValidUntil && vm.validUntil) {
      textAt(`Valid Until  ${vm.validUntil}`, rightX, logoTop + 60, {
        width: rightW, align: "right", font: "Regular", size: 9, lineBreak: false,
      });
    }
    if (vm.profile.showAgainstInvoice && vm.parentDocNumber) {
      textAt(`Against Invoice  ${vm.parentDocNumber}`, rightX, logoTop + 60, {
        width: rightW, align: "right", font: "Regular", size: 9, lineBreak: false,
      });
    }

    let leftY = logoTop + logoH + 10;
    leftY = textAt(vm.supplier.name || "", PAGE.marginLeft, leftY, {
      width: CONTENT_WIDTH * 0.55, font: "Bold", size: 11, lineBreak: false,
    });
    leftY = textAt(vm.supplier.address_line || "", PAGE.marginLeft, leftY + 2, {
      width: CONTENT_WIDTH * 0.55, font: "Regular", size: 9, lineBreak: true,
    });

    let y = leftY + 14;
    y = textAt("Customer", PAGE.marginLeft, y, {
      width: CONTENT_WIDTH * 0.65, font: "Bold", size: 10, lineBreak: false,
    });
    y = textAt(vm.customer.name || "", PAGE.marginLeft, y + 2, {
      width: CONTENT_WIDTH * 0.65, font: "Bold", size: 10, lineBreak: false,
    });
    if (vm.customer.address_line?.trim()) {
      y = textAt(vm.customer.address_line, PAGE.marginLeft, y + 1, {
        width: CONTENT_WIDTH * 0.65, font: "Regular", size: 9, lineBreak: true,
      });
    }
    return y + 12;
  }

  function estimateFooterHeight() {
    let h = 10;
    h += 12;
    h += Math.max(1, vm.notes.length) * 11;
    h += 8;
    h += 12;
    h += 6 * 11;
    h += 28;
    return Math.max(h, 5 * 13 + 20);
  }

  function drawTableHeader(y) {
    const h = 16;
    doc.rect(PAGE.marginLeft, y, CONTENT_WIDTH, h).fillAndStroke("#E8E8E8", "#222");
    let x = PAGE.marginLeft;
    const headers = ["#", "Name", "Unit", "Qty", "Pieces", "Unit Price", "Total Price"];
    doc.fillColor("#111").font("Bold").fontSize(8);
    for (let i = 0; i < COLS.length; i++) {
      doc.text(headers[i], x + 2, y + 4, {
        width: COLS[i].width - 4, align: COLS[i].align, lineBreak: false,
      });
      x += COLS[i].width;
    }
    resetCursor(y + h);
    return y + h;
  }

  function drawLineRow(y, line, rowH, compact) {
    doc.rect(PAGE.marginLeft, y, CONTENT_WIDTH, rowH).stroke("#333");
    let x = PAGE.marginLeft;
    const cells = [line.lineNo, null, line.uom, line.qty, line.pieces, line.unitPrice, line.lineTotal];
    const nameSize = compact ? 7 : 8;
    const descSize = compact ? 6.5 : 7.5;
    for (let i = 0; i < COLS.length; i++) {
      if (i === 1) {
        let cellY = y + 3;
        doc.font("Bold").fontSize(nameSize).fillColor("#111");
        const nameH = doc.heightOfString(line.name || " ", { width: COLS[i].width - 6 });
        const nameMaxH = Math.max(0, rowH - 6);
        doc.text(line.name || " ", x + 3, cellY, { width: COLS[i].width - 6, height: nameMaxH });
        cellY += Math.min(nameH + 1, nameMaxH);
        if (line.description && cellY < y + rowH - 4) {
          doc.font("Regular").fontSize(descSize).fillColor("#333");
          doc.text(line.description, x + 3, cellY, {
            width: COLS[i].width - 6,
            height: Math.max(0, y + rowH - cellY - 2),
          });
        }
      } else {
        doc.font("Regular").fontSize(compact ? 7 : 7.5).fillColor("#111").text(
          cells[i],
          x + 2,
          y + 4,
          { width: COLS[i].width - 4, align: COLS[i].align, lineBreak: false },
        );
      }
      doc.moveTo(x, y).lineTo(x, y + rowH).stroke("#333");
      x += COLS[i].width;
    }
    doc.moveTo(PAGE.marginLeft + CONTENT_WIDTH, y)
      .lineTo(PAGE.marginLeft + CONTENT_WIDTH, y + rowH)
      .stroke("#333");
    resetCursor(y + rowH);
    return y + rowH;
  }

  let y = drawHeader();
  y = drawTableHeader(y);

  const footerH = estimateFooterHeight();
  const tableBudget = Math.max(40, PAGE_BOTTOM - footerH - y);
  const naturalHeights = vm.lines.map((line) =>
    measureNameCell(doc, line.name, line.description, TABLE_COLS.name, false),
  );
  const naturalTotal = naturalHeights.reduce((a, b) => a + b, 0) || 1;
  const compact = naturalTotal > tableBudget;
  const minH = Math.max(6, Math.min(12, Math.floor(tableBudget / Math.max(1, vm.lines.length))));
  let rowHeights = naturalHeights;
  if (compact) {
    rowHeights = naturalHeights.map((h) => Math.max(minH, Math.floor((h * tableBudget) / naturalTotal)));
    let sum = rowHeights.reduce((a, b) => a + b, 0);
    let i = rowHeights.length - 1;
    while (sum > tableBudget && i >= 0) {
      const cut = Math.min(rowHeights[i] - minH, sum - tableBudget);
      rowHeights[i] -= cut;
      sum -= cut;
      i -= 1;
    }
    if (sum > tableBudget && rowHeights.length > 0) {
      const target = Math.max(5, Math.floor(tableBudget / rowHeights.length));
      rowHeights = rowHeights.map(() => target);
    }
  }

  for (let i = 0; i < vm.lines.length; i++) {
    y = drawLineRow(y, vm.lines[i], rowHeights[i], compact);
  }

  y += 10;
  const leftW = CONTENT_WIDTH * 0.58;
  const rightX = PAGE.marginLeft + leftW + 8;
  const totalsW = CONTENT_WIDTH - leftW - 8;
  const notesStartY = y;

  let notesY = notesStartY;
  notesY = textAt("Notes:", PAGE.marginLeft, notesY, { width: leftW, font: "Bold", size: 9 });
  if (vm.notes.length === 0) {
    notesY = textAt("-", PAGE.marginLeft, notesY, { width: leftW, size: 8.5 });
  } else {
    for (const n of vm.notes) {
      notesY = textAt(`- ${n}`, PAGE.marginLeft, notesY, { width: leftW, size: 8.5 });
    }
  }
  notesY += 8;
  notesY = textAt("Terms & Conditions:", PAGE.marginLeft, notesY, { width: leftW, font: "Bold", size: 9 });
  notesY = textAt(`Payment: ${vm.terms.payment || ""}`, PAGE.marginLeft, notesY, { width: leftW, size: 8.5 });
  notesY = textAt("Delivery:", PAGE.marginLeft, notesY, { width: leftW, size: 8.5 });
  notesY = textAt(`Place: ${vm.terms.delivery_place || ""}`, PAGE.marginLeft, notesY, { width: leftW, size: 8.5 });
  notesY = textAt(`Time: ${vm.terms.delivery_days || ""}`, PAGE.marginLeft, notesY, { width: leftW, size: 8.5 });
  notesY = textAt(`Validity: ${vm.terms.validity_days || ""}`, PAGE.marginLeft, notesY, { width: leftW, size: 8.5 });
  notesY = textAt(`Warrenty: ${vm.terms.warranty || ""}`, PAGE.marginLeft, notesY, { width: leftW, size: 8.5 });

  const totalRows = [
    ["Total", vm.subtotalDisplay, false],
    [vm.discountLabel, vm.discountDisplay, false],
    ["S. Total", vm.afterDiscountDisplay, false],
    [vm.taxLabel, vm.taxDisplay, false],
    ["G. Total", vm.grandTotalDisplay, true],
  ];
  let ty = notesStartY;
  const labelW = totalsW * 0.55;
  const amtW = totalsW * 0.45;
  for (const [lbl, amt, grand] of totalRows) {
    textAt(lbl, rightX, ty, {
      width: labelW, align: "left", font: grand ? "Bold" : "Regular", size: grand ? 10 : 9, lineBreak: false,
    });
    textAt(amt, rightX + labelW, ty, {
      width: amtW, align: "right", font: grand ? "Bold" : "Regular", size: grand ? 10 : 9, lineBreak: false,
    });
    ty += grand ? 14 : 12;
  }

  const sigY = Math.max(notesY, ty) + 16;
  const sigW = leftW * 0.48;
  const sig2X = PAGE.marginLeft + leftW * 0.52;
  const preparedLine = [vm.preparedBy.name, vm.preparedBy.phone].filter(Boolean).join(" ");
  const approvedLine = [vm.approvedBy.name, vm.approvedBy.phone].filter(Boolean).join(" ");

  textAt(preparedLine || " ", PAGE.marginLeft, sigY, { width: sigW, size: 8.5, lineBreak: false });
  textAt(approvedLine || " ", sig2X, sigY, { width: sigW, size: 8.5, lineBreak: false });
  textAt("Prepared By Phone Number", PAGE.marginLeft, sigY + 14, { width: sigW, size: 8, lineBreak: false });
  textAt("Approved By Contact", sig2X, sigY + 14, { width: sigW, size: 8, lineBreak: false });

  if (vm.amountInWords) {
    textAt(`Amount in words: ${vm.amountInWords}`, PAGE.marginLeft, sigY + 36, {
      width: CONTENT_WIDTH, size: 8,
    });
  }

  doc.end();
  const raw = await done;
  return freezePdfId(raw, JSON.stringify(input));
}

const chunks = [];
process.stdin.on("data", (c) => chunks.push(c));
process.stdin.on("end", async () => {
  try {
    const input = JSON.parse(Buffer.concat(chunks).toString("utf8"));
    const pdf = await renderPdf(input);
    process.stdout.write(pdf);
  } catch (err) {
    console.error(String(err?.stack || err));
    process.exit(1);
  }
});
