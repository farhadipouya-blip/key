<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * کاربر سامانه کلید — یک حساب مشترک برای تمام ماژول‌ها
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar_path',
        'bio',
        'national_code',
        'field_group',
        'grade',
        'city',
        'is_active',
        'last_login_at',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    // ------------------------------------------------------------------
    // نقش‌ها و مجوزها (RBAC سفارشی)
    // ------------------------------------------------------------------

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withPivot(['assigned_by', 'assigned_at']);
    }

    public function roleSlugs(): BelongsToMany
    {
        return $this->roles();
    }

    public function hasRole(string|array $slugs): bool
    {
        $slugs = (array) $slugs;
        return $this->roles->pluck('slug')->intersect($slugs)->isNotEmpty();
    }

    /** مدیر کل یا مدیر */
    public function isAdmin(): bool
    {
        return $this->hasRole(['superadmin', 'admin']);
    }

    /** کارمند سیستم (هر نقش غیر دانشجو) */
    public function isStaff(): bool
    {
        return $this->roles->whereNotIn('slug', ['student'])->isNotEmpty() || $this->isAdmin();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role', 'permission_id', 'permission_id')
            ->whereIn('permission_role.role_id', $this->roles->pluck('id'));
    }

    public function canAccess(string $permissionSlug): bool
    {
        if ($this->hasRole('superadmin')) {
            return true;
        }
        $rolePermissionSlugs = $this->roles->flatMap(fn ($r) => $r->permissions->pluck('slug'))->unique();

        return $rolePermissionSlugs->contains($permissionSlug);
    }

    /** مسیر داشبورد بر اساس نقش */
    public function dashboardRoute(): string
    {
        if ($this->hasRole('superadmin') || $this->hasRole('admin')) {
            return route('admin.dashboard');
        }
        if ($this->hasRole('counselor')) {
            return route('counselor.dashboard');
        }
        if ($this->hasRole('teacher')) {
            return route('teacher.dashboard');
        }
        return route('student.dashboard');
    }

    // ------------------------------------------------------------------
    // روابط ماژول‌ها
    // ------------------------------------------------------------------

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class, 'created_by');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function planItems(): HasMany
    {
        return $this->hasMany(\App\Models\PlanItem::class);
    }

    public function studyPlans(): HasMany
    {
        return $this->hasMany(StudyPlan::class);
    }

    public function studySessions(): HasMany
    {
        return $this->hasMany(StudySession::class);
    }

    public function summaries(): HasMany
    {
        return $this->hasMany(Summary::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function rankEstimates(): HasMany
    {
        return $this->hasMany(RankEstimate::class);
    }

    public function historicalResults(): HasMany
    {
        return $this->hasMany(HistoricalResult::class);
    }

    public function mastery(): HasMany
    {
        return $this->hasMany(MasteryLevel::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function counselingSessions(): HasMany
    {
        return $this->hasMany(CounselingSession::class, 'student_id');
    }

    /** مشاورانی که این دانشجو را پشتیبانی می‌کنند */
    public function counselors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'counselor_student', 'student_id', 'counselor_id')
            ->wherePivot('status', 'active');
    }

    /** دانشجویان تحت پشتیبانی مشاور */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'counselor_student', 'counselor_id', 'student_id')
            ->wherePivot('status', 'active')
            ->withPivot(['status', 'note', 'assigned_by']);
    }

    public function chatRooms(): BelongsToMany
    {
        return $this->belongsToMany(ChatRoom::class, 'chat_room_user', 'user_id', 'chat_room_id')
            ->withPivot('last_read_message_id', 'joined_at');
    }

    public function walletBalance(): float
    {
        return (float) ($this->walletTransactions()->latest('id')->value('balance_after') ?? 0);
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->exists();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeStudents($query)
    {
        return $query->whereHas('roles', fn ($q) => $q->where('slug', 'student'));
    }
}
