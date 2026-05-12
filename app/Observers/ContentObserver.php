<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ContentObserver
{
    public function created(Model $model): void
    {
        ActivityLog::record('content.created', $model, [], auth()->user());
    }

    public function updated(Model $model): void
    {
        ActivityLog::record('content.updated', $model, $model->getChanges(), auth()->user());
    }

    public function deleted(Model $model): void
    {
        ActivityLog::record('content.deleted', $model, [], auth()->user());
    }
}
