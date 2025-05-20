<?php

namespace App\Console\Commands;

use App\Services\SupabaseStorageService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TestSupabaseUpload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:supabase-upload';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa o upload de um arquivo para o Supabase';

    /**
     * @var SupabaseStorageService
     */
    protected $supabaseStorage;

    /**
     * Create a new command instance.
     */
    public function __construct(SupabaseStorageService $supabaseStorage)
    {
        parent::__construct();
        $this->supabaseStorage = $supabaseStorage;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testando upload para o Supabase...');
        $this->info('URL: ' . config('services.supabase.url'));
        $this->info('Bucket: ' . config('services.supabase.bucket'));
        
        // Verificar configurações do Supabase sem chamar o comando diretamente
        $url = config('services.supabase.url');
        $key = config('services.supabase.key');
        $bucket = config('services.supabase.bucket');
        
        if (empty($url) || empty($key) || empty($bucket)) {
            $this->error('As configurações do Supabase estão incompletas!');
            $this->info('Verifique seu arquivo .env e adicione as configurações necessárias.');
            return 1;
        }
        
        // Sabemos que o bucket existe, então vamos pular a verificação
        $this->info('Assumindo que o bucket existe (verificado manualmente no painel do Supabase)...');
        $this->info("Continuando com o teste...");
        
        // Criar uma imagem temporária para teste
        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir . '/test_image.png';
        
        // Criar uma imagem simples
        $width = 200;
        $height = 200;
        $image = imagecreatetruecolor($width, $height);
        
        // Preencher com uma cor
        $backgroundColor = imagecolorallocate($image, 0, 120, 255);
        imagefill($image, 0, 0, $backgroundColor);
        
        // Desenhar um texto
        $textColor = imagecolorallocate($image, 255, 255, 255);
        $text = 'Test';
        
        // Centralizar o texto
        $fontsize = 5;
        $textWidth = imagefontwidth($fontsize) * strlen($text);
        $textHeight = imagefontheight($fontsize);
        $x = ($width - $textWidth) / 2;
        $y = ($height - $textHeight) / 2;
        
        imagestring($image, $fontsize, $x, $y, $text, $textColor);
        
        // Salvar a imagem
        imagepng($image, $tempFile);
        imagedestroy($image);
        
        $this->info('Imagem de teste criada em: ' . $tempFile);
        
        // Criar um objeto UploadedFile
        $uploadedFile = new UploadedFile(
            $tempFile,
            'test_image.png',
            'image/png',
            null,
            true
        );
        
        // Fazer upload para o Supabase
        $this->info('Fazendo upload para o Supabase...');
        $this->info('Usando método: upload() na classe SupabaseStorageService');
        
        $timestamp = time();
        $testFilename = "test_{$timestamp}.png";
        $url = $this->supabaseStorage->upload($uploadedFile, null, $testFilename);
        
        if ($url) {
            $this->info('Upload bem-sucedido!');
            $this->info('URL da imagem: ' . $url);
            
            // Verificar se conseguimos acessar a imagem
            $this->info('Verificando acesso à imagem...');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($statusCode >= 200 && $statusCode < 300) {
                $this->info('Imagem acessível via URL pública.');
            } else {
                $this->warn("Imagem não acessível via URL pública (status: {$statusCode}).");
                $this->info('Verifique as permissões do bucket no Supabase.');
            }
            
            return 0;
        } else {
            $this->error('Falha no upload para o Supabase.');
            $this->info('Verifique os logs para mais detalhes.');
            
            // Verificar logs recentes
            $logFile = storage_path('logs/laravel.log');
            if (file_exists($logFile)) {
                $this->info('Últimas 10 linhas do log:');
                $logs = file($logFile);
                $lastLogs = array_slice($logs, -10);
                foreach ($lastLogs as $log) {
                    $this->line($log);
                }
            }
            
            return 1;
        }
    }
} 