<?php

namespace App\Enums;

enum ImportBatchStatus: string
{
    case Draft = 'draft';
    case Preview = 'preview';
    case Confirmed = 'confirmed';
    case Failed = 'failed';
}