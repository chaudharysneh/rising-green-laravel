<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\HasCustomFields;
use App\Traits\OwnedByUser;

class Task extends Model
{
    use HasFactory, HasCustomFields, OwnedByUser;

    protected $fillable = [
        'title',
        'description',
        'related_type',
        'related_id',
        'estimate_id',
        'project_id',
        'assigned_user_id',
        'user_id',
        'due_date',
        'priority',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (Task $task) {
            $task->status = static::normalizeStatusValue($task->status);
            static::syncOwnedUserFromAssignee($task);
        });
    }

    public static function normalizeStatusValue(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        $normalized = strtolower(trim($status));
        $normalized = str_replace([' ', '-'], '_', $normalized);

        return match ($normalized) {
            'inprogress' => 'in_progress',
            default => $normalized,
        };
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'related_id');
    }

    public function estimate()
    {
        return $this->belongsTo(Estimate::class, 'estimate_id', 'estimate_id');
    }

    public function statusHistories()
    {
        return $this->morphMany(ModuleStatusHistory::class, 'historable')->latest();
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable')->latest();
    }
}
