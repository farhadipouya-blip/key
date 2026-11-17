<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Notifications\TicketReplyNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * تیکت پشتیبانی — نمای کاربر ثبت‌کننده
 * وضعیت‌ها: open → answered (پاسخ کارمند) → pending (پاسخ کاربر) → closed
 */
class TicketController extends Controller
{
    public const CATEGORIES = ['فنی', 'آموزشی', 'مالی', 'پیشنهاد'];

    public const PRIORITIES = ['low' => 'کم', 'normal' => 'معمولی', 'high' => 'بالا', 'urgent' => 'فوری'];

    public function index(Request $request)
    {
        $status = (string) $request->query('status');

        $tickets = Ticket::query()
            ->where('user_id', $request->user()->id)
            ->with(['assignee:id,name', 'messages'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('last_reply_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('tickets.index', compact('tickets', 'status'));
    }

    public function create()
    {
        return view('tickets.create', [
            'categories' => self::CATEGORIES,
            'priorities' => self::PRIORITIES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'category' => ['required', 'string', 'in:' . implode(',', self::CATEGORIES)],
            'priority' => ['required', 'in:' . implode(',', array_keys(self::PRIORITIES))],
            'body' => ['required', 'string', 'max:10000'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ], [
            'subject.required' => 'موضوع تیکت الزامی است.',
            'body.required' => 'متن تیکت الزامی است.',
            'attachment.max' => 'حجم پیوست حداکثر ۱۰ مگابایت است.',
        ]);

        $ticket = Ticket::create([
            'user_id' => $request->user()->id,
            'subject' => $data['subject'],
            'category' => $data['category'],
            'priority' => $data['priority'],
            'status' => 'open',
            'last_reply_at' => now(),
        ]);

        $message = new TicketMessage([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
            'is_staff' => false,
        ]);
        if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
            $message->attachment_path = $request->file('attachment')->store('tickets', 'public');
        }
        $ticket->messages()->save($message);

        $this->notifyStaff($ticket, 'تیکت جدید ثبت شد', 'تیکت «' . $ticket->subject . '» توسط ' . $request->user()->name . ' ثبت شد.');

        AuditLog::record($request->user(), 'ticket.created', $ticket, ['priority' => $ticket->priority]);

        return redirect()->route('tickets.show', $ticket)->with('success', 'تیکت شما با موفقیت ثبت شد و در اسرع وقت پاسخ داده می‌شود.');
    }

    /** نمایش تیکت — مالک یا کارمند دارای مجوز پشتیبانی */
    public function show(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        $isSupport = $user->canAccess('tickets.support');

        if ($ticket->user_id !== $user->id && ! $isSupport) {
            abort(403, 'این تیکت متعلق به شما نیست.');
        }

        $ticket->load(['messages.user:id,name,email', 'user:id,name,email', 'assignee:id,name']);

        return view('tickets.show', [
            'ticket' => $ticket,
            'isSupport' => $isSupport,
            'priorities' => self::PRIORITIES,
        ]);
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        $isSupport = $user->canAccess('tickets.support');

        if ($ticket->user_id !== $user->id && ! $isSupport) {
            abort(403, 'این تیکت متعلق به شما نیست.');
        }

        if ($ticket->status === 'closed') {
            return back()->with('error', 'این تیکت بسته شده است و امکان پاسخ‌دهی ندارد.');
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ], [
            'body.required' => 'متن پاسخ الزامی است.',
            'attachment.max' => 'حجم پیوست حداکثر ۱۰ مگابایت است.',
        ]);

        $message = new TicketMessage([
            'user_id' => $user->id,
            'body' => $data['body'],
            'is_staff' => $isSupport && $ticket->user_id !== $user->id,
        ]);
        if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
            $message->attachment_path = $request->file('attachment')->store('tickets', 'public');
        }
        $ticket->messages()->save($message);

        // گذار وضعیت: پاسخ کارمند → answered؛ پاسخ کاربر → pending
        $staffReplied = $message->is_staff;
        $ticket->forceFill([
            'status' => $staffReplied ? 'answered' : 'pending',
            'assigned_to' => $staffReplied ? ($ticket->assigned_to ?: $user->id) : $ticket->assigned_to,
            'last_reply_at' => now(),
        ])->save();

        // اعلان به طرف مقابل
        if ($staffReplied) {
            $ticket->user->notify(new TicketReplyNotification(
                $ticket,
                'پاسخ پشتیبانی دریافت شد',
                'پاسخ جدیدی برای تیکت «' . $ticket->subject . '» ثبت شد.'
            ));
        } else {
            $this->notifyStaff($ticket, 'پاسخ جدید در تیکت', $user->name . ' به تیکت «' . $ticket->subject . '» پاسخ داد.');
        }

        AuditLog::record($user, 'ticket.replied', $ticket, ['is_staff' => $message->is_staff]);

        return back()->with('success', 'پاسخ شما ثبت شد.');
    }

    public function close(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        $isSupport = $user->canAccess('tickets.support');

        if ($ticket->user_id !== $user->id && ! $isSupport) {
            abort(403, 'این تیکت متعلق به شما نیست.');
        }

        if ($ticket->status === 'closed') {
            return back()->with('info', 'این تیکت قبلاً بسته شده است.');
        }

        $ticket->forceFill(['status' => 'closed', 'closed_at' => now()])->save();

        if ($ticket->user_id !== $user->id) {
            $ticket->user->notify(new TicketReplyNotification(
                $ticket,
                'تیکت بسته شد',
                'تیکت «' . $ticket->subject . '» توسط پشتیبانی بسته شد.'
            ));
        }

        AuditLog::record($user, 'ticket.closed', $ticket);

        return back()->with('success', 'تیکت بسته شد.');
    }

    /** اطلاع‌رسانی تیکت به همه کارمندانی که مجوز پشتیبانی دارند */
    private function notifyStaff(Ticket $ticket, string $title, string $body): void
    {
        $permId = Permission::query()->where('slug', 'tickets.support')->value('id');

        $staff = User::query()
            ->whereHas('roles', fn ($r) => $r->whereHas('permissions', fn ($p) => $p->where('permissions.id', $permId)))
            ->where('is_active', true)
            ->when($ticket->assigned_to, fn ($q) => $q->orWhere('id', $ticket->assigned_to))
            ->where('id', '!=', $ticket->user_id)
            ->get();

        foreach ($staff as $member) {
            $member->notify(new TicketReplyNotification($ticket, $title, $body));
        }
    }
}
