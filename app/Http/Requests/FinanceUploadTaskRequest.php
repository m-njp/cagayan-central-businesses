<?php

namespace App\Http\Requests;

use App\Models\FinanceUpload;

class FinanceUploadTaskRequest extends FinanceReportRequest
{
    public function upload(): FinanceUpload
    {
        $upload = $this->route('upload');
        abort_unless($upload instanceof FinanceUpload && $upload->municipality_id === $this->town()->id
            && $upload->fiscal_year === $this->reportYear(), 404);

        return $upload;
    }
}
