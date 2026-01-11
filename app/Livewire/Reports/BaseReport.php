<?php

namespace App\Livewire\Reports;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

abstract class BaseReport extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $title = 'Report';
    public int $perPage = 25;
    public string $fromDate = '';
    public string $toDate = '';

    protected string $dateField = 'date';
    protected array $filterFields = [];

    public function mount(): void
    {
        $this->authorize('report.view');
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
        $this->initFilters();
    }

    protected function initFilters(): void
    {
        // Override in child reports to load filter options.
    }

    public function updating($field): void
    {
        $resetFields = array_merge(['fromDate', 'toDate', 'perPage'], $this->filterFields);

        if (in_array($field, $resetFields, true)) {
            $this->resetPage();
        }
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
    }

    abstract protected function columns(): array;

    abstract protected function baseQuery();

    protected function applyFilters($query)
    {
        return $query
            ->when($this->fromDate, fn ($q) => $q->whereDate($this->dateField, '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate($this->dateField, '<=', $this->toDate));
    }

    protected function paginatedRows(): LengthAwarePaginator
    {
        $query = $this->applyFilters($this->baseQuery());
        $rows = $query->paginate($this->perPage);
        $columns = array_keys($this->columns());

        return $rows->through(function ($row) use ($columns) {
            $data = [];
            foreach ($columns as $column) {
                $data[$column] = $row->{$column} ?? '';
            }

            return $data;
        });
    }

    protected function exportRows(): array
    {
        $query = $this->applyFilters($this->baseQuery());
        $rows = $query->get();
        $columns = array_keys($this->columns());

        return $rows->map(function ($row) use ($columns) {
            $data = [];
            foreach ($columns as $column) {
                $data[$column] = $row->{$column} ?? '';
            }

            return $data;
        })->all();
    }

    public function exportExcel()
    {
        $columns = array_values($this->columns());
        $rows = $this->exportRows();
        $filename = $this->exportFilename('csv');

        return response()->streamDownload(function () use ($columns, $rows) {
            $output = fopen('php://output', 'w');
            fputcsv($output, $columns);

            foreach ($rows as $row) {
                fputcsv($output, array_values($row));
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportPdf()
    {
        $columns = array_values($this->columns());
        $rows = array_map('array_values', $this->exportRows());
        $filename = $this->exportFilename('pdf');

        $pdf = Pdf::loadView('reports.pdf-table', [
            'title' => $this->title,
            'fromDate' => $this->fromDate,
            'toDate' => $this->toDate,
            'columns' => $columns,
            'rows' => $rows,
        ]);

        return response()->streamDownload(fn () => print($pdf->output()), $filename);
    }

    protected function exportFilename(string $extension): string
    {
        $slug = Str::slug($this->title);

        return ($slug ?: 'report').'-'.now()->format('Ymd_His').'.'.$extension;
    }

    protected function filterConfig(): array
    {
        return [];
    }

    public function render()
    {
        return view('livewire.reports.table', [
            'title' => $this->title,
            'columns' => $this->columns(),
            'rows' => $this->paginatedRows(),
            'filters' => $this->filterConfig(),
        ])->with(['title_name' => $this->title]);
    }
}
