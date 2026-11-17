<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * میز پشتیبانی: همه تیکت‌ها، فیلتر وضعیت/اولویت، ارجاع به کارشناس
 */
class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $status = (string) $request->query('status');
        $priority = (string) $request->query('priority');
        $assigned = (string) $request->query('assigned'); // mine|unassigned

        $tickets = Ticket::query()
            ->with(['user:id,name,email', 'assignee:id,name'])
            ->withCount('messages')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($priority !== '', fn ($query) => $query->where('priority', $priority))
            ->when($assigned === 'mine', fn ($query) => $query->where('assigned_to', $request->user()->id))
            ->when($assigned === 'unassigned', fn ($query) => $query->whereNull('assigned_to'))
            ->orderByRaw("FIELD(status, 'open', 'pending', 'answered', 'closed')")
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
            ->orderBy('last_reply_at')
            ->paginate(15)
            ->withQueryString();

        $experts = User::query()
            ->whereHas('roles.permissions', fn ($p) => $p->where('slug', 'tickets.support'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $counts = [
            'open' => Ticket::query()->where('status', 'open')->count(),
            'pending' => Ticket::query()->where('status', 'pending')->count(),
            'answered' => Ticket::query()->where('status', 'answered')->count(),
            'closed' => Ticket::query()->where('status', 'closed')->count(),
        ];

        return view('support.tickets.index', compact('tickets', 'experts', 'counts', 'status', 'priority', 'assigned'));
    }

    /** ارجاع تیکت به کارشناس */
    public function assign(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
        ]);

        $expert = User::findOrFail($data['assigned_to']);

        if (! $expert->canAccess('tickets.support')) {
            return back()->with('error', 'کاربر انتخاب‌شده مجوز پشتیبانی تیکت ندارد.');
        }

        $wasNew = $ticket->assigned_to !== $expert->id;
        $ticket->forceFill(['assigned_to' => $expert->id])->save();

        if ($wasNew) {
            $expert->notify(new \App\Notifications\TicketReplyNotification(
                $ticket,
                'تیکت به شما ارجاع شد',
                'تیکت «' . $ticket->subject . '» به شما ارجاع شد.'
            ));
        }

        AuditLog::record($request->user(), 'ticket.assigned', $ticket, ['assigned_to' => $expert->id]);

        return back()->with('success', 'تیکت به «' . $expert->name . '» ارجاع شد.');
    }
}
