import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import * as XLSX from 'xlsx';

/**
 * Genera y descarga un archivo Excel (.xlsx) con los datos proporcionados.
 * @param {Array} data - Array de objetos para convertir en filas
 * @param {Array} columns - Array de { header: string, key: string } para definir columnas
 * @param {string} filename - Nombre del archivo sin extensión
 */
export function exportarExcel(data, columns, filename) {
  const worksheetData = data.map(item => {
    const row = {};
    columns.forEach(col => {
      row[col.header] = item[col.key] ?? '';
    });
    return row;
  });

  const worksheet = XLSX.utils.json_to_sheet(worksheetData);

  // Auto-width: calcular ancho máximo por columna
  const colWidths = columns.map(col => {
    const maxLen = Math.max(
      col.header.length,
      ...data.map(item => String(item[col.key] ?? '').length)
    );
    return { wch: Math.min(maxLen + 2, 50) };
  });
  worksheet['!cols'] = colWidths;

  const workbook = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(workbook, worksheet, 'Reporte');

  const timestamp = new Date().toISOString().replace(/[:.]/g, '').slice(0, 15);
  XLSX.writeFile(workbook, `${filename}_${timestamp}.xlsx`);
}

/**
 * Genera y descarga un archivo PDF con tabla usando jsPDF + autotable.
 * @param {string} title - Título del reporte
 * @param {Array} data - Array de objetos para las filas
 * @param {Array} columns - Array de { header: string, key: string }
 * @param {string} filename - Nombre del archivo sin extensión
 * @param {Object} options - Opciones adicionales { subtitle, footer }
 */
export function exportarPDF(title, data, columns, filename, options = {}) {
  const doc = new jsPDF();
  const pageWidth = doc.internal.pageSize.getWidth();

  // Título
  doc.setFontSize(16);
  doc.setFont('helvetica', 'bold');
  doc.text(title, 14, 20);

  // Subtítulo / período
  if (options.subtitle) {
    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');
    doc.text(options.subtitle, 14, 28);
  }

  // Fecha de generación
  const now = new Date().toLocaleString('es-ES', {
    year: 'numeric', month: '2-digit', day: '2-digit',
    hour: '2-digit', minute: '2-digit'
  });
  doc.setFontSize(8);
  doc.text(`Generado: ${now}`, pageWidth - 14, 20, { align: 'right' });

  // Tabla
  const headers = columns.map(c => c.header);
  const rows = data.map(item => columns.map(c => item[c.key] ?? ''));

  autoTable(doc, {
    head: [headers],
    body: rows,
    startY: options.subtitle ? 34 : 26,
    theme: 'grid',
    headStyles: { fillColor: [29, 53, 87], textColor: 255, fontStyle: 'bold', fontSize: 9 },
    bodyStyles: { fontSize: 8 },
    alternateRowStyles: { fillColor: [240, 240, 240] },
    styles: { cellPadding: 2, overflow: 'linebreak' },
  });

  // Footer
  const finalY = doc.lastAutoTable.finalY || 200;
  if (options.footer) {
    doc.setFontSize(8);
    doc.text(options.footer, 14, finalY + 10);
  }
  doc.setFontSize(7);
  doc.text('Reporte generado automaticamente por el sistema.', 14, finalY + 18);

  // Descargar
  const timestamp = new Date().toISOString().replace(/[:.]/g, '').slice(0, 15);
  doc.save(`${filename}_${timestamp}.pdf`);
}
