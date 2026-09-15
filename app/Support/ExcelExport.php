<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Builds a consistently styled .xlsx download: a title block (judul, subjudul, active filters,
 * export time), a bold header row, bordered data rows, typed columns (real Excel dates/times),
 * fitted column widths, a frozen header, and print setup. One or more lembar.
 *
 * Column definitions are either a plain label (text) or [label, tipe] with tipe one of
 * teks | angka | tanggal | waktu | jam | panjang.
 */
final class ExcelExport
{
    public const string TIPE_TEKS = 'teks';

    public const string TIPE_ANGKA = 'angka';

    public const string TIPE_TANGGAL = 'tanggal';

    public const string TIPE_WAKTU = 'waktu';

    public const string TIPE_JAM = 'jam';

    public const string TIPE_PANJANG = 'panjang';

    private const string WARNA_HEADER = '0F172A';

    private const string WARNA_GARIS = 'CBD5E1';

    private const string WARNA_REDUP = '64748B';

    private const int LEBAR_MIN = 6;

    private const int LEBAR_MAKS = 60;

    private const int LEBAR_PANJANG = 50;

    /** @var list<string> */
    private array $subjudul = [];

    private string $filter = 'Semua data (tanpa filter)';

    /**
     * @var list<array{nama: string, kolom: list<array{0: string, 1: string}>, baris: list<list<mixed>>}>
     */
    private array $lembar = [];

    private function __construct(private readonly string $judul) {}

    public static function buat(string $judul): self
    {
        return new self($judul);
    }

    public function subjudul(string ...$baris): self
    {
        $this->subjudul = [...$this->subjudul, ...$baris];

        return $this;
    }

    /**
     * Active filters as label => value; empty values are skipped.
     *
     * @param  array<string, string|int|null>  $filter
     */
    public function filter(array $filter): self
    {
        $aktif = collect($filter)
            ->filter(fn ($nilai) => $nilai !== null && trim((string) $nilai) !== '')
            ->map(fn ($nilai, string $label) => "{$label} = {$nilai}");

        if ($aktif->isNotEmpty()) {
            $this->filter = $aktif->implode(' · ');
        }

        return $this;
    }

    /**
     * Start a new lembar (sheet); kolom() and baris() apply to the most recent one.
     */
    public function lembar(string $nama): self
    {
        $this->lembar[] = ['nama' => $nama, 'kolom' => [], 'baris' => []];

        return $this;
    }

    /**
     * @param  string|array{0: string, 1: string}  ...$kolom
     */
    public function kolom(string|array ...$kolom): self
    {
        $this->pastikanLembar();

        $this->lembar[array_key_last($this->lembar)]['kolom'] = array_map(
            fn (string|array $definisi) => is_array($definisi) ? [$definisi[0], $definisi[1]] : [$definisi, self::TIPE_TEKS],
            array_values($kolom),
        );

        return $this;
    }

    /**
     * @param  iterable<int, list<mixed>>  $baris
     */
    public function baris(iterable $baris): self
    {
        $this->pastikanLembar();

        $this->lembar[array_key_last($this->lembar)]['baris'] = collect($baris)->values()->all();

        return $this;
    }

    /**
     * Stream the workbook as "{nama}_{tanggal}.xlsx".
     */
    public function unduh(string $nama): StreamedResponse
    {
        $spreadsheet = $this->spreadsheet();
        $namaFile = Str::slug($nama).'_'.now()->format('Y-m-d').'.xlsx';

        return response()->streamDownload(
            fn () => (new Xlsx($spreadsheet))->save('php://output'),
            $namaFile,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    public function spreadsheet(): Spreadsheet
    {
        $this->pastikanLembar();

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()->setTitle($this->judul)->setCreator(config('app.name'));
        $spreadsheet->removeSheetByIndex(0);

        foreach ($this->lembar as $lembar) {
            $this->tulisLembar($spreadsheet->createSheet(), $lembar);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function pastikanLembar(): void
    {
        if ($this->lembar === []) {
            $this->lembar('Data');
        }
    }

    /**
     * @param  array{nama: string, kolom: list<array{0: string, 1: string}>, baris: list<list<mixed>>}  $lembar
     */
    private function tulisLembar(Worksheet $sheet, array $lembar): void
    {
        $sheet->setTitle(mb_substr(str_replace(['\\', '/', '?', '*', '[', ']', ':'], '-', $lembar['nama']), 0, 31));

        $kolom = $lembar['kolom'];
        $jumlahKolom = max(count($kolom), 1);
        $kolomAkhir = Coordinate::stringFromColumnIndex($jumlahKolom);

        // Title block.
        $barisJudul = [$this->judul, ...$this->subjudul, 'Filter: '.$this->filter, 'Diekspor: '.now()->isoFormat('D MMM YYYY HH:mm').(auth()->user() ? ' oleh '.auth()->user()->name : '')];

        foreach ($barisJudul as $indeks => $teks) {
            $nomor = $indeks + 1;
            $sheet->setCellValueExplicit("A{$nomor}", $teks, DataType::TYPE_STRING);
            $sheet->mergeCells("A{$nomor}:{$kolomAkhir}{$nomor}");
        }

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getRowDimension(1)->setRowHeight(24);

        if (count($barisJudul) > 1) {
            $sheet->getStyle('A2:A'.count($barisJudul))->getFont()->setSize(10)->getColor()->setRGB(self::WARNA_REDUP);
        }

        $barisHeader = count($barisJudul) + 2;
        $barisDataAwal = $barisHeader + 1;
        $barisDataAkhir = $barisHeader + max(count($lembar['baris']), 1);

        // Header row.
        foreach ($kolom as $indeks => [$label]) {
            $sheet->setCellValueExplicit([$indeks + 1, $barisHeader], $label, DataType::TYPE_STRING);
        }

        $header = $sheet->getStyle("A{$barisHeader}:{$kolomAkhir}{$barisHeader}");
        $header->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::WARNA_HEADER);
        $header->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension($barisHeader)->setRowHeight(22);

        // Data rows.
        if ($lembar['baris'] === []) {
            $sheet->setCellValueExplicit("A{$barisDataAwal}", 'Tidak ada data.', DataType::TYPE_STRING);
            $sheet->mergeCells("A{$barisDataAwal}:{$kolomAkhir}{$barisDataAwal}");
            $sheet->getStyle("A{$barisDataAwal}")->getFont()->setItalic(true)->getColor()->setRGB(self::WARNA_REDUP);
            $sheet->getStyle("A{$barisDataAwal}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        foreach ($lembar['baris'] as $indeksBaris => $baris) {
            $nomor = $barisDataAwal + $indeksBaris;

            foreach ($kolom as $indeksKolom => [, $tipe]) {
                $this->tulisSel($sheet, $indeksKolom + 1, $nomor, $baris[$indeksKolom] ?? null, $tipe);
            }
        }

        $tabel = $sheet->getStyle("A{$barisHeader}:{$kolomAkhir}{$barisDataAkhir}");
        $tabel->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::WARNA_GARIS);

        if ($lembar['baris'] !== []) {
            $sheet->getStyle("A{$barisDataAwal}:{$kolomAkhir}{$barisDataAkhir}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        }

        // Column widths, formats and alignment per tipe.
        foreach ($kolom as $indeks => [$label, $tipe]) {
            $huruf = Coordinate::stringFromColumnIndex($indeks + 1);
            $rentang = "{$huruf}{$barisDataAwal}:{$huruf}{$barisDataAkhir}";
            $nilai = array_column($lembar['baris'], $indeks);

            $sheet->getColumnDimension($huruf)->setWidth($this->lebarKolom($label, $tipe, $nilai));

            match ($tipe) {
                self::TIPE_ANGKA => $sheet->getStyle($rentang)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER),
                self::TIPE_TANGGAL => $sheet->getStyle($rentang)->getNumberFormat()->setFormatCode('dd mmm yyyy'),
                self::TIPE_WAKTU => $sheet->getStyle($rentang)->getNumberFormat()->setFormatCode('dd mmm yyyy hh:mm'),
                self::TIPE_JAM => $sheet->getStyle($rentang)->getNumberFormat()->setFormatCode('hh:mm'),
                default => $sheet->getStyle($rentang)->getAlignment()->setWrapText(true),
            };

            if (in_array($tipe, [self::TIPE_TANGGAL, self::TIPE_WAKTU, self::TIPE_JAM], true)) {
                $sheet->getStyle($rentang)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }

        // Frozen header and print setup.
        $sheet->freezePane("A{$barisDataAwal}");
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setRowsToRepeatAtTopByStartAndEnd($barisHeader, $barisHeader);
        $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.4)->setRight(0.4);
    }

    private function tulisSel(Worksheet $sheet, int $kolom, int $baris, mixed $nilai, string $tipe): void
    {
        if ($nilai === null || $nilai === '') {
            return;
        }

        if ($nilai instanceof DateTimeInterface) {
            $sheet->setCellValue([$kolom, $baris], match ($tipe) {
                self::TIPE_JAM => Date::PHPToExcel($nilai) - floor(Date::PHPToExcel($nilai)),
                self::TIPE_TANGGAL => floor(Date::PHPToExcel($nilai)),
                default => Date::PHPToExcel($nilai),
            });

            return;
        }

        if (is_bool($nilai)) {
            $nilai = $nilai ? 'Ya' : 'Tidak';
        }

        if ($tipe === self::TIPE_ANGKA && is_numeric($nilai)) {
            $sheet->setCellValue([$kolom, $baris], $nilai + 0);

            return;
        }

        // Explicit strings: NPM/kode stay text and leading "=" is never treated as a formula.
        $sheet->setCellValueExplicit([$kolom, $baris], (string) $nilai, DataType::TYPE_STRING);
    }

    /**
     * @param  list<mixed>  $nilai
     */
    private function lebarKolom(string $label, string $tipe, array $nilai): int
    {
        $tetap = match ($tipe) {
            self::TIPE_TANGGAL => 14,
            self::TIPE_WAKTU => 19,
            self::TIPE_JAM => 9,
            self::TIPE_PANJANG => self::LEBAR_PANJANG,
            default => null,
        };

        if ($tetap !== null) {
            return max($tetap, mb_strlen($label) + 2);
        }

        $terpanjang = collect($nilai)
            ->map(fn ($isi) => is_bool($isi) ? 5 : collect(explode("\n", (string) $isi))->max(fn (string $potongan) => mb_strlen($potongan)))
            ->push(mb_strlen($label))
            ->max();

        return (int) min(max($terpanjang + 3, self::LEBAR_MIN), self::LEBAR_MAKS);
    }
}
