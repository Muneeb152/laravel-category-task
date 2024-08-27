<?php

namespace App\Exports;

use App\Models\Task;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TasksExport implements FromCollection,WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Task::all();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Description',
            'Start Date',
            'End Date',
            'Category Id',
            'Image',
            'Created At',
            'Updated At',
        ];
    }
}
