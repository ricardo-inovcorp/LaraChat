<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SupabaseStorageService
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $bucket;

    public function __construct()
    {
        $this->apiUrl = config('services.supabase.url');
        $this->apiKey = config('services.supabase.key');
        $this->bucket = config('services.supabase.bucket');
        
        Log::info('SupabaseStorageService inicializado', [
            'apiUrl' => $this->apiUrl,
            'keyExists' => !empty($this->apiKey),
            'bucket' => $this->bucket
        ]);
    }

    /**
     * Upload a file to Supabase Storage
     *
     * @param UploadedFile $file The file to upload
     * @param string|null $path Optional path within the bucket
     * @param string|null $filename Optional custom filename
     * @return string|null The public URL of the uploaded file or null on failure
     */
    public function upload(UploadedFile $file, ?string $path = null, ?string $filename = null): ?string
    {
        try {
            // Verificar configurações
            Log::info('Iniciando upload para Supabase com as seguintes configurações', [
                'apiUrl' => $this->apiUrl,
                'bucketExists' => !empty($this->bucket),
                'apiKeyLength' => strlen($this->apiKey)
            ]);
            
            // Generate a filename if not provided
            if (!$filename) {
                $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            }

            // Handle path properly
            $fullPath = $path ? trim($path, '/') . '/' . $filename : $filename;
            
            Log::info('Tentando fazer upload para Supabase', [
                'bucket' => $this->bucket,
                'fullPath' => $fullPath,
                'mime' => $file->getMimeType(),
                'size' => $file->getSize()
            ]);

            // Get the file contents
            $fileContents = file_get_contents($file->getRealPath());
            
            if ($fileContents === false) {
                Log::error('Não foi possível ler o arquivo temporário');
                return null;
            }

            // Tentativa com cURL diretamente para o endpoint Storage
            Log::info('Tentando upload via cURL para o endpoint Storage API');
            
            $ch = curl_init("{$this->apiUrl}/storage/v1/object/avatars/{$fullPath}");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $fileContents,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->apiKey,
                    'Content-Type: ' . $file->getMimeType(),
                    'apikey: ' . $this->apiKey // Adicionando apikey header explicitamente
                ],
                CURLOPT_VERBOSE => true
            ]);
            
            $response = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            Log::info('Resposta do upload via cURL', [
                'status' => $statusCode,
                'response' => $response,
                'error' => $error
            ]);
            
            if ($statusCode >= 200 && $statusCode < 300) {
                $publicUrl = "{$this->apiUrl}/storage/v1/object/public/avatars/{$fullPath}";
                Log::info('URL do arquivo gerada', ['url' => $publicUrl]);
                return $publicUrl;
            }
            
            // Tentativa direta com o form-data
            Log::info('Tentando upload via multipart form-data');
            
            // Preparar o arquivo para envio multipart
            $file_path = $file->getRealPath();
            
            // Criar uma requisição cURL com multipart/form-data
            $cfile = curl_file_create($file_path, $file->getMimeType(), $filename);
            $post_data = ['file' => $cfile];
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => "{$this->apiUrl}/storage/v1/object/avatars/{$fullPath}",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $post_data,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->apiKey,
                    'apikey: ' . $this->apiKey
                ],
                CURLOPT_VERBOSE => true
            ]);
            
            $response = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            Log::info('Resposta do upload via form-data', [
                'status' => $statusCode,
                'response' => $response,
                'error' => $error
            ]);
            
            if ($statusCode >= 200 && $statusCode < 300) {
                $publicUrl = "{$this->apiUrl}/storage/v1/object/public/avatars/{$fullPath}";
                Log::info('URL do arquivo gerada (via form-data)', ['url' => $publicUrl]);
                return $publicUrl;
            }
            
            Log::error('Falha no upload para Supabase após múltiplas tentativas', [
                'status' => $statusCode,
                'error' => $error,
                'response' => $response
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('Exceção no upload: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Delete a file from Supabase Storage
     *
     * @param string $path The path of the file to delete
     * @return bool Whether the deletion was successful
     */
    public function delete(string $path): bool
    {
        try {
            $path = trim($path, '/');
            
            Log::info('Tentando excluir arquivo do Supabase', [
                'path' => $path
            ]);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->delete("{$this->apiUrl}/storage/v1/object/{$this->bucket}/{$path}");

            Log::info('Resposta da exclusão do Supabase', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Supabase delete exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get the public URL for a file in Supabase Storage
     *
     * @param string $path The path of the file
     * @return string The public URL
     */
    public function getPublicUrl(string $path): string
    {
        $path = trim($path, '/');
        return "{$this->apiUrl}/storage/v1/object/public/{$this->bucket}/{$path}";
    }
} 