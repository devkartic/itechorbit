<?php

namespace App\AiPro\Http;

use App\AiPro\Contracts\AiProvider;
use App\AiPro\Contracts\Retriever;
use App\AiPro\DatabaseRetriever;
use App\AiPro\Models\Conversation;
use App\AiPro\Models\Message;
use App\AiPro\Models\Site;
use App\AiPro\ProviderFailure;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

class ChatController extends Controller
{
    public function config(Request $request)
    {
        $site = $request->attributes->get('ai_pro_site');

        return response()->json(['name' => 'iTechOrbit AI', 'website' => $site->name, 'welcome' => $site->welcome, 'color' => $site->color,
            'contact_url' => $site->contact_url, 'notice' => 'AI answers may be imperfect. Chats are stored for 30 days. Do not share sensitive information.',
            'suggestions' => ['What does this website offer?', 'How can I get in touch?']]);
    }

    public function conversation(Request $request)
    {
        $site = $request->attributes->get('ai_pro_site');
        $key = 'ai-pro-session:'.$site->id.':'.$request->ip();
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 60);
        $token = Str::random(64);
        $conversation = Conversation::create(['site_id' => $site->id, 'public_id' => (string) Str::uuid(),
            'token_hash' => hash('sha256', $token), 'expires_at' => now()->addDays(config('ai_pro.retention_days'))]);

        return response()->json(['conversation_id' => $conversation->public_id, 'token' => $token], 201);
    }

    public function message(Request $request)
    {
        $input = $request->validate(['conversation_id' => ['required', 'string', 'max:64'], 'question' => ['required', 'string', 'max:2000']]);
        $site = $request->attributes->get('ai_pro_site');
        $conversation = Conversation::where('site_id', $site->id)->where('public_id', $input['conversation_id'])->where('expires_at', '>', now())->first();
        abort_unless($conversation && $request->bearerToken() && hash_equals($conversation->token_hash, hash('sha256', $request->bearerToken())), 403);
        $key = 'ai-pro-chat:'.$site->id.':'.$request->ip();
        abort_if(RateLimiter::tooManyAttempts($key, $site->minute_limit), 429);
        RateLimiter::hit($key, 60);
        $message = DB::transaction(function () use ($site, $conversation, $input) {
            Site::whereKey($site->id)->lockForUpdate()->firstOrFail();
            abort_if(Message::where('site_id', $site->id)->where('created_at', '>=', now()->startOfDay())->count() >= $site->daily_limit, 429, 'This website has reached its daily question limit.');

            return Message::create(['site_id' => $site->id, 'conversation_id' => $conversation->id, 'question' => $input['question']]);
        });
        try {
            $history = Message::where('site_id', $site->id)->where('conversation_id', $conversation->id)->where('status', 'answered')->latest('id')->limit(4)->get()->reverse()
                ->map(fn ($m) => ['question' => $m->question, 'answer' => $m->answer])->values()->all();
            $search = implode("\n", [...array_column($history, 'question'), $input['question']]);
            $context = app(Retriever::class)->search($site, $search);
            $answer = ['answer' => $this->fallback($input['question']), 'source_ids' => [], 'usage' => []];
            if ($context) {
                $answer = app(AiProvider::class)->answer($input['question'], $context, $history);
            }
            $eligible = app(DatabaseRetriever::class)->eligibleSources($site);
            if (collect($context)->contains(fn ($item) => ! isset($eligible[$item['id']]) || $eligible[$item['id']]->checksum !== $item['checksum'])) {
                $answer = ['answer' => $this->fallback($input['question']), 'source_ids' => [], 'usage' => $answer['usage']];
            }
            abort_unless($site->fresh()?->enabled, 503);
            $sources = collect($context)->whereIn('id', array_filter($answer['source_ids'], 'is_int'))->unique('id')->map(fn ($item) => ['title' => $item['title'], 'url' => $item['url']])->filter(fn ($item) => $item['url'])->values()->all();
            $status = $sources ? 'answered' : 'unsupported';
            if (! $sources) {
                $answer['answer'] = $this->fallback($input['question']);
            }
            $message->update(['answer' => $answer['answer'], 'sources' => $sources, 'status' => $status, 'usage' => $answer['usage']]);

            return response()->json(['answer' => $message->answer, 'sources' => $sources, 'status' => $status, 'contact_url' => $site->contact_url]);
        } catch (ProviderFailure $error) {
            $quota = $error->reason === 'quota';
            $message->update(['status' => $quota ? 'quota_exceeded' : 'unavailable']);

            return response()->json(['message' => $quota ? 'The AI provider has reached its usage limit. Please try again later.' : 'iTechOrbit AI is temporarily unavailable. Please use the contact page.',
                'status' => $message->status, 'contact_url' => $site->contact_url], $quota ? 429 : 503)
                ->header('Retry-After', (string) ($error->retryAfter ?? 60));
        } catch (Throwable) {
            $message->update(['status' => 'unavailable']);

            return response()->json(['message' => 'iTechOrbit AI is temporarily unavailable. Please try again or use the contact page.', 'status' => 'unavailable', 'contact_url' => $site->contact_url], 503);
        }
    }

    private function fallback(string $question): string
    {
        return preg_match('/[\x{0980}-\x{09FF}]/u', $question)
            ? 'ওয়েবসাইটের তথ্য থেকে এই প্রশ্নের উত্তর পাওয়া যায়নি। আরও জানতে যোগাযোগের পৃষ্ঠাটি দেখুন।'
            : 'I could not find an answer in this website’s information. Please visit the contact page for help.';
    }
}
