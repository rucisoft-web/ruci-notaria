<?php

namespace App\Services\Ai;

use App\Models\AiPermintaan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klien tipis untuk OpenAI Responses API dengan keluaran terstruktur (JSON schema).
 * Setiap panggilan dicatat di ai_permintaan beserta token dan perkiraan biaya.
 */
class OpenAiClient
{
    /**
     * @param  list<array<string, mixed>>  $content  bagian input pengguna (input_text, input_image, input_file)
     * @param  array<string, mixed>  $schema
     * @param  array{fitur: string, berkas_id?: int|null, subjek?: Model|null, ringkasan?: string|null}  $log
     * @return array<string, mixed>
     */
    public function json(string $instruksi, array $content, string $namaSchema, array $schema, array $log): array
    {
        $model = config('notaria.ai.model');
        $entry = new AiPermintaan([
            'user_id' => auth()->id(), 'fitur' => $log['fitur'], 'berkas_id' => $log['berkas_id'] ?? null,
            'model' => $model, 'status' => 'gagal', 'ringkasan' => isset($log['ringkasan']) ? mb_substr($log['ringkasan'], 0, 2000) : null,
        ]);
        if ($log['subjek'] ?? null) {
            $entry->subjekAi()->associate($log['subjek']);
        }

        try {
            $res = Http::withToken((string) config('notaria.ai.api_key'))
                ->timeout(config('notaria.ai.timeout'))
                ->acceptJson()
                ->post(rtrim(config('notaria.ai.base_url'), '/').'/responses', [
                    'model' => $model,
                    'instructions' => $instruksi,
                    'input' => [['role' => 'user', 'content' => $content]],
                    'text' => ['format' => ['type' => 'json_schema', 'name' => $namaSchema, 'schema' => $schema, 'strict' => true]],
                    'store' => false,
                ]);

            if ($res->failed()) {
                throw new RuntimeException('Layanan AI menolak permintaan (HTTP '.$res->status().'): '.mb_substr((string) $res->json('error.message', $res->body()), 0, 300));
            }

            $teks = collect($res->json('output', []))
                ->where('type', 'message')
                ->flatMap(fn ($m) => $m['content'] ?? [])
                ->where('type', 'output_text')
                ->pluck('text')->implode('');
            $data = json_decode($teks, true);
            if (! is_array($data)) {
                throw new RuntimeException('Jawaban AI bukan JSON yang sah.');
            }

            $in = (int) $res->json('usage.input_tokens', 0);
            $out = (int) $res->json('usage.output_tokens', 0);
            $entry->fill([
                'status' => 'berhasil', 'input_tokens' => $in, 'output_tokens' => $out,
                'biaya_usd' => ($in * config('notaria.ai.harga_input_per_juta') + $out * config('notaria.ai.harga_output_per_juta')) / 1_000_000,
            ])->save();

            return $data;
        } catch (\Throwable $e) {
            $entry->galat = mb_substr($e->getMessage(), 0, 1000);
            $entry->save();

            throw $e instanceof RuntimeException ? $e : new RuntimeException('Gagal menghubungi layanan AI: '.$e->getMessage(), 0, $e);
        }
    }
}
