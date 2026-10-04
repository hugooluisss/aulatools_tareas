<?php

declare(strict_types=1);

namespace App\Reports\Renderer;

use App\Reports\Model\RenderedReport;
use App\Reports\Model\ReportViewModel;

interface ReportRenderer
{
    public function render(ReportViewModel $viewModel): RenderedReport;
}
