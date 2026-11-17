<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\PollVote;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * چت آنلاین: اتاق‌های خصوصی/گروهی/کلاس، پیام متنی/فایل/تصویر/صوتی،
 * نظرسنجی، وایت‌برد مشترک (ذخیره وضعیت در پیام system)
 */
class ChatController extends Controller
{
    private const MAX_FILE_KB = 10240; // حداکثر ۱۰ مگابایت

    public function index(Request $request)
    {
        $user = $request->user();

        $rooms = $user->chatRooms()
            ->with(['users:users.id,users.name', 'messages' => fn ($q) => $q->reorder('id', 'desc')->take(1)])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get()
            ->each(fn ($room) => $room->setRelation('users', $room->users->filter(fn ($u) => $u->id !== $user->id)->values()));

        // پشتیبان‌ها (کارمندان فعال) برای شروع گفتگوی خصوصی
        $staff = User::query()
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->whereHas('roles', fn ($r) => $r->where('slug', '!=', 'student'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $contacts = User::query()
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('chat.index', compact('rooms', 'staff', 'contacts'));
    }

    /** ساخت گروه با انتخاب کاربران */
    public function groupStore(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ], ['name.required' => 'نام گروه الزامی است.']);

        $room = ChatRoom::create([
            'type' => 'group',
            'name' => $data['name'],
            'created_by' => $request->user()->id,
        ]);

        $members = collect($data['user_ids'] ?? [])->push($request->user()->id)->unique()->values()->all();
        $room->users()->syncWithPivotValues($members, ['joined_at' => now()]);

        ChatMessage::create([
            'chat_room_id' => $room->id,
            'user_id' => $request->user()->id,
            'type' => 'system',
            'body' => 'گروه «' . $room->name . '» ساخته شد.',
        ]);

        return redirect()->route('chat.room', $room)->with('success', 'گروه «' . $room->name . '» ساخته شد.');
    }

    /** شروع گفتگوی خصوصی با کارمند */
    public function privateStore(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $target = User::findOrFail($data['user_id']);
        if ($target->id === $request->user()->id) {
            return back()->with('error', 'نمی‌توانید با خودتان گفتگو کنید.');
        }
        if (! $target->is_active) {
            return back()->with('error', 'این کاربر غیرفعال است.');
        }

        $room = ChatRoom::privateBetween($request->user(), $target);

        return redirect()->route('chat.room', $room);
    }

    /** نمایش اتاق: پیام‌ها + فرم ارسال + نظرسنجی + وایت‌برد */
    public function room(Request $request, ChatRoom $room)
    {
        $this->authorizeRoom($request, $room);

        $messages = $room->messages()
            ->with('user:id,name')
            ->with('votes')
            ->reorder('id', 'desc')
            ->take(80)
            ->get()
            ->reverse()
            ->values();

        $members = $room->users()->orderBy('name')->get(['users.id', 'users.name']);
        $unread = $room->messages()->where('id', '>', (int) ($room->users()->where('users.id', $request->user()->id)->first()?->pivot->last_read_message_id ?? 0))->count();

        // آخرین وضعیت وایت‌برد
        $whiteboard = $room->messages()
            ->where('type', 'system')
            ->where('body', 'like', '{"wb"%')
            ->orderByDesc('id')
            ->first();

        return view('chat.room', compact('room', 'messages', 'members', 'whiteboard'));
    }

    /** ارسال پیام: متن/فایل/تصویر/صوت/نظرسنجی/وایت‌برد */
    public function send(Request $request, ChatRoom $room)
    {
        $this->authorizeRoom($request, $room);

        $data = $request->validate([
            'type' => ['required', Rule::in(['text', 'file', 'image', 'audio', 'poll', 'system'])],
            'body' => ['nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:' . self::MAX_FILE_KB],
            'duration' => ['nullable', 'integer', 'min:1', 'max:600'],
            'poll_options' => ['nullable', 'array', 'min:2', 'max:10'],
            'poll_options.*' => ['required', 'string', 'max:120'],
        ], [
            'attachment.max' => 'حجم فایل حداکثر ۱۰ مگابایت است.',
            'poll_options.min' => 'نظرسنجی باید حداقل دو گزینه داشته باشد.',
            'body.max' => 'متن پیام حداکثر ۵۰۰۰ کاراکتر است.',
        ]);

        $type = $data['type'];
        $message = new ChatMessage([
            'chat_room_id' => $room->id,
            'user_id' => $request->user()->id,
            'type' => $type,
        ]);

        if ($type === 'system') {
            // وایت‌برد: ذخیره/به‌روزرسانی وضعیت نقاط به‌صورت json
            $payload = json_decode((string) $request->input('body'), true);
            if (! is_array($payload) || ! ($payload['wb'] ?? false) || ! isset($payload['strokes'])) {
                return response()->json(['error' => 'نقاشی نامعتبر است.'], 422);
            }
            $payload['strokes'] = array_slice($payload['strokes'], -300); // محدودیت حجم

            $existing = $room->messages()
                ->where('type', 'system')->where('body', 'like', '{"wb"%')
                ->orderByDesc('id')->first();

            if ($existing) {
                $existing->forceFill(['body' => json_encode($payload, JSON_UNESCAPED_UNICODE), 'updated_at' => now()])->save();
                $this->touchRoom($room, $existing);

                return response()->json(['ok' => true, 'updated' => true, 'id' => $existing->id]);
            }

            $message->body = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $message->save();
            $this->touchRoom($room, $message);

            return response()->json(['ok' => true, 'id' => $message->id]);
        }

        if ($type === 'poll') {
            $message->body = (string) $request->input('poll_question', $request->input('body', 'نظرسنجی'));
            $message->poll_options = array_values($data['poll_options'] ?? []);
            $message->save();
            $this->touchRoom($room, $message);

            return response()->json(['ok' => true, 'id' => $message->id]);
        }

        if ($type === 'text') {
            if (trim((string) $request->input('body')) === '') {
                return response()->json(['error' => 'متن پیام خالی است.'], 422);
            }
            $message->body = trim((string) $request->input('body'));
            $message->save();
            $this->touchRoom($room, $message);

            return response()->json(['ok' => true, 'id' => $message->id]);
        }

        // فایل/تصویر/صوت
        if (! $request->hasFile('attachment') || ! $request->file('attachment')->isValid()) {
            return response()->json(['error' => 'فایلی برای ارسال انتخاب نشده است.'], 422);
        }

        $file = $request->file('attachment');
        $mime = (string) $file->getMimeType();
        $sizeKb = (int) ceil($file->getSize() / 1024);

        if ($sizeKb > self::MAX_FILE_KB) {
            return response()->json(['error' => 'حجم فایل حداکثر ۱۰ مگابایت است.'], 422);
        }

        $allowed = [
            'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            'audio' => ['audio/webm', 'video/webm', 'audio/ogg', 'audio/mpeg', 'audio/mp4', 'audio/wav', 'audio/x-wav', 'audio/aac'],
        ];
        if (isset($allowed[$type]) && ! in_array($mime, $allowed[$type], true)) {
            return response()->json(['error' => 'نوع فایل با پیام هم‌خوانی ندارد.'], 422);
        }
        if ($type === 'file' && str_starts_with($mime, 'audio/')) {
            $type = 'audio';
        }

        $message->file_path = $file->store('chat', 'public');
        $message->file_name = $file->getClientOriginalName();
        $message->file_size = $file->getSize();
        if ($type === 'audio' && $request->filled('duration')) {
            $message->duration_seconds = (int) $data['duration'];
        }
        $message->type = $type;
        $message->save();
        $this->touchRoom($room, $message);

        return response()->json(['ok' => true, 'id' => $message->id]);
    }

    /** دریافت پیام‌های جدید برای polling */
    public function fetch(Request $request, ChatRoom $room)
    {
        $this->authorizeRoom($request, $room);

        $afterId = max(0, (int) $request->query('after_id', 0));

        $query = $room->messages()
            ->with(['user:id,name', 'votes:user_id,chat_message_id,option_index']);

        // پس از last_id: پیام‌های جدید به ترتیب طبیعی؛ بار اول: ۵۰ پیام آخر به ترتیب زمانی
        // (reorder برای حذف ترتیب پیش‌فرض oldest رابطه messages)
        $messages = ($afterId > 0
            ? $query->where('id', '>', $afterId)->reorder('id')->take(200)->get()
            : $query->reorder('id', 'desc')->take(50)->get()->reverse()->values());

        $me = $request->user()->id;
        $payload = $messages->map(fn (ChatMessage $m) => $this->serialize($m, $me))->values()->all();

        $whiteboard = $room->messages()
            ->where('type', 'system')->where('body', 'like', '{"wb"%')
            ->orderByDesc('id')->first();

        // علامت‌گذاری خواندن
        if ($room->users()->where('users.id', $me)->exists()) {
            $latest = $room->messages()->orderByDesc('id')->value('id');
            if ($latest) {
                $room->users()->updateExistingPivot($me, ['last_read_message_id' => $latest]);
            }
        }

        return response()->json([
            'messages' => $payload,
            'last_id' => (int) ($messages->last()?->id ?? $afterId),
            'whiteboard' => $whiteboard ? json_decode((string) $whiteboard->body, true) : null,
        ]);
    }

    /** رأی به نظرسنجی */
    public function vote(Request $request, ChatRoom $room, ChatMessage $message)
    {
        $this->authorizeRoom($request, $room);

        if ($message->chat_room_id !== $room->id || $message->type !== 'poll') {
            abort(404);
        }

        $options = (array) ($message->poll_options ?? []);
        $data = $request->validate([
            'option_index' => ['required', 'integer', 'min:0', 'max:' . (count($options) - 1)],
        ]);

        PollVote::updateOrCreate(
            ['chat_message_id' => $message->id, 'user_id' => $request->user()->id],
            ['option_index' => $data['option_index']]
        );

        $this->touchRoom($room, $message);

        $votes = $message->votes()->get(['user_id', 'option_index']);
        $counts = collect($options)->map(fn ($_, $i) => $votes->where('option_index', $i)->count())->values();
        $myVote = $votes->firstWhere('user_id', $request->user()->id)?->option_index;

        return response()->json([
            'ok' => true,
            'counts' => $counts,
            'total' => $votes->count(),
            'my_vote' => $myVote,
        ]);
    }

    // ------------------------------------------------------------------

    /** کنترل عضویت اتاق در سمت سرور */
    private function authorizeRoom(Request $request, ChatRoom $room): void
    {
        if (! $room->users()->where('users.id', $request->user()->id)->exists()) {
            abort(403, 'شما عضو این اتاق گفتگو نیستید.');
        }
    }

    private function touchRoom(ChatRoom $room, ChatMessage $message): void
    {
        $room->forceFill(['last_message_at' => $message->created_at ?? now()])->save();
    }

    private function serialize(ChatMessage $m, int $meId): array
    {
        $data = [
            'id' => $m->id,
            'type' => $m->type,
            'body' => $m->body,
            'user' => $m->user ? ['id' => $m->user->id, 'name' => $m->user->name] : null,
            'is_mine' => (int) $m->user_id === $meId,
            'created_at' => $m->created_at?->toIso8601String(),
            'time' => fa_datetime($m->created_at),
        ];

        if ($m->file_path) {
            $data['file_url'] = storage_public_url($m->file_path);
            $data['file_name'] = $m->file_name;
            $data['file_size'] = $m->file_size;
            $data['duration_seconds'] = $m->duration_seconds;
        }

        if ($m->type === 'poll') {
            $votes = $m->votes;
            $options = (array) ($m->poll_options ?? []);
            $data['poll_options'] = $options;
            $data['poll_counts'] = collect($options)->map(fn ($_, $i) => $votes->where('option_index', $i)->count())->values()->all();
            $data['poll_total'] = $votes->count();
            $data['my_vote'] = $votes->firstWhere('user_id', $meId)?->option_index;
        }

        return $data;
    }
}
