<?php

namespace App\Filament\Resources\BarangResource\Pages;

use App\Filament\Resources\BarangResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Forms;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Support\Collection;

class ListBarangs extends ListRecords
{
    protected static string $resource = BarangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Barang')
                ->icon('heroicon-o-plus'),

            Actions\Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->form([
                    Forms\Components\Select::make('kategori_id')
                        ->label('Filter Kategori')
                        ->relationship('kategori', 'nama_kategori')
                        ->preload()
                        ->searchable()
                        ->placeholder('Semua Kategori')
                        ->native(false),
                    Forms\Components\Toggle::make('preview')
                        ->label('Tampilkan Preview')
                        ->default(true),
                ])
                ->action(function (array $data) {
                    // Check if preview is enabled
                    if (isset($data['preview']) && $data['preview']) {
                        // Redirect to preview page with parameters
                        $params = [];
                        if (!empty($data['kategori_id'])) {
                            $params['kategori_id'] = $data['kategori_id'];
                        }

                        $url = route('barang.preview-pdf', $params);
                        // Open in new tab
                        return redirect()->away($url);
                    }

                    // Jika tidak preview, lanjutkan dengan download langsung
                    $query = \App\Models\Barang::with('kategori');

                    if (!empty($data['kategori_id'])) {
                        $query->where('kategori_id', $data['kategori_id']);
                    }

                    $barang = $query->get();

                    $pdf = Pdf::loadView('exports.barang', [
                        'barang' => $barang,
                        'tanggal' => now()->format('d/m/Y'),
                    ]);

                    $filename = 'data-barang-' . now()->format('YmdHis') . '.pdf';

                    return response()->streamDownload(
                        fn() => print($pdf->output()),
                        $filename
                    );
                })
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            BulkAction::make('exportSelected')
            ->label('Export Terpilih')
            ->icon('heroicon-o-document-arrow-down')
            ->form([
                Forms\Components\Toggle::make('show_footer')
                    ->label('Tampilkan Footer')
                    ->default(true),
                Forms\Components\Toggle::make('preview')
                    ->label('Tampilkan Preview')
                    ->default(true),
            ])
            ->action(function (Collection $records, array $data) {
                // Check if preview is enabled
                if (isset($data['preview']) && $data['preview']) {
                    // Get IDs of selected records
                    $selectedIds = $records->pluck('id')->toArray();
                    
                    // Redirect to preview page with selected IDs
                    $params = [
                        'selected_ids' => $selectedIds,
                        'paper_size' => $data['paper_size'] ?? 'a4',
                        'show_footer' => $data['show_footer'] ?? true,
                    ];
                    
                    $url = route('barang.preview-pdf', $params);
                    // Open in new tab
                    return redirect()->away($url);
                }
                
                // Jika tidak preview, lanjutkan dengan download langsung
                $records->load('kategori');
                
                $pdf = Pdf::loadView('exports.barang', [
                    'barang' => $records,
                    'tanggal' => now()->format('d/m/Y'),
                    'title' => 'Data Barang Terpilih',
                    'showFooter' => $data['show_footer'] ?? true,
                ]);
                
                // Set paper size
                $pdf->setPaper($data['paper_size'] ?? 'a4');
                
                $filename = 'data-barang-selected-' . now()->format('YmdHis') . '.pdf';
                
                return response()->streamDownload(
                    fn () => print($pdf->output()),
                    $filename
                );
            })
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return "Data barang";
    }
}
